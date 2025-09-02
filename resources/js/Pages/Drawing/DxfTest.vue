<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { Head, useForm } from '@inertiajs/vue3'
import DxfParser from 'dxf-parser'

/* ====== Props dal server ====== */
const props = defineProps({
  dxfText: { type: String, default: '' },
  title:   { type: String, default: 'DXF Viewer (sfondo "retino" + "maniglia")' },
  record:  { type: Object, default: null } // {IdRigaDXF, Codice, Descrizione, LRG, ALT}
})

/* ====== Stato ====== */
const fileName        = ref('')
const drawables       = ref([])
const layersMap       = ref({})
const stylesMap       = ref({})
const blocksMap       = ref(new Map())

const showFill        = ref(true)
const showRetinoDebug = ref(false)
const showTextDebug   = ref(false)

// Tweak regolabili da UI (debug verticale testo)
const topTweak     = ref(-0.28)
const bottomTweak  = ref(+0.08)

// Debug loop sfondi
const retinoLoops   = ref([])
const manigliaLoops = ref([])

/* ====== Costanti rendering ====== */
const LINE_SCALE    = 2
const FILL_RETINO   = '#ADD8E6'
const FILL_MANIGLIA = '#000000'
const TWO_PI        = Math.PI * 2

/* ====== Form (metadati + DXF) ====== */
const isEdit = computed(() => !!props.record?.IdRigaDXF)
const form = useForm({
  IdRigaDXF:   props.record?.IdRigaDXF ?? null,
  Codice:      props.record?.Codice ?? '',
  Descrizione: props.record?.Descrizione ?? '',
  LRG:         props.record?.LRG ?? '',
  ALT:         props.record?.ALT ?? '',
  // invio lato server: o come testo (Dxf) o come file (dxf_file)
  Dxf:         props.dxfText || '',
  dxf_file:    null
})

/* ====== Colori & Spessori ====== */
function aciToHex(n){ if(n==null||n===7) return '#000'
  const m={1:'#F00',2:'#FF0',3:'#0F0',4:'#0FF',5:'#00F',6:'#F0F',8:'#808080',9:'#C0C0C0'}
  return m[n]||'#000'
}
function trueColorToHex(tc){ if(tc==null) return null
  const hex=Number(tc>>>0).toString(16).padStart(6,'0'); return `#${hex.slice(-6)}`
}
function strokeOf(ent){
  return trueColorToHex(ent.trueColor)
      ?? (ent.colorNumber!=null?aciToHex(ent.colorNumber):null)
      ?? (layersMap.value[ent.layer]?.colorNumber!=null?aciToHex(layersMap.value[ent.layer].colorNumber):'#000')
}
function strokeWidthOf(ent){
  const lw=ent.lineweight ?? ent.lineWeight ?? layersMap.value[ent.layer]?.lineWeight ?? -1
  return (lw>0? Math.max(0.4,lw/100):1.2)*LINE_SCALE
}

/* ====== Matrici 2D ====== */
const matId = ()=>[1,0,0,1,0,0]
const matMul=(a,b)=>[a[0]*b[0]+a[2]*b[1],a[1]*b[0]+a[3]*b[1],a[0]*b[2]+a[2]*b[3],a[1]*b[2]+a[3]*b[3],a[0]*b[4]+a[2]*b[5]+a[4],a[1]*b[4]+a[3]*b[5]+a[5]]
const T=(x,y)=>[1,0,0,1,x,y]
const S=(x,y)=>[x,0,0,y,0,0]
const R=d=>{const r=Math.PI*d/180,c=Math.cos(r),s=Math.sin(r);return [c,s,-s,c,0,0]}
const applyM=(m,p)=>({x:m[0]*p.x+m[2]*p.y+m[4], y:m[1]*p.x+m[3]*p.y+m[5]})

/* ====== Util testo ====== */
function uniformScale(m){ const sx=Math.hypot(m[0],m[1]); const sy=Math.hypot(m[2],m[3]); return (sx+sy)/2 }
function rotationDegFromM(m){ return Math.atan2(m[1],m[0])*180/Math.PI }
function textHeightFor(ent,M){ const st=stylesMap.value[ent.textStyle]||stylesMap.value[ent.styleName]; let h=ent.height??ent.textHeight??0; if(st?.height>0) h=st.height; if(!(h>0)) h=10; return h*uniformScale(M) }
function mtextHeightFor(ent,M){ const st=stylesMap.value[ent.textStyle]||stylesMap.value[ent.styleName]; let h=ent.height??ent.textHeight??10; if(st?.height>0) h=st.height; return h*uniformScale(M) }
function textAngle(ent,M){ const a=ent.rotation??ent.rotationAngle??0; return a+rotationDegFromM(M) }

function mapTextAnchor(h){
  // 0 Left, 1 Center, 2 Right, 3 Aligned, 4 Middle, 5 Fit
  if (h===2) return 'end'
  if (h===1 || h===4 || h===3 || h===5) return 'middle'
  return 'start'
}
function mapTextBaseline(v){
  // 0 Baseline, 1 Bottom, 2 Middle, 3 Top
  if (v===3) return 'text-before-edge'
  if (v===2) return 'middle'
  if (v===1) return 'text-after-edge'
  return 'alphabetic'
}
function mapMTextAnchor(ap){
  // 1 TL,2 TC,3 TR,4 ML,5 MC,6 MR,7 BL,8 BC,9 BR
  if (ap===2 || ap===5 || ap===8) return 'middle'
  if (ap===3 || ap===6 || ap===9) return 'end'
  return 'start'
}
function mapMTextBaseline(ap){
  if (ap<=3) return 'text-before-edge'
  if (ap<=6) return 'middle'
  return 'text-after-edge'
}

/* ====== Caricamento DXF ====== */
async function onFile(ev){
  const f=ev.target.files?.[0]; if(!f) return
  fileName.value=f.name
  form.dxf_file = f
  const text=new TextDecoder('utf-8').decode(await f.arrayBuffer())
  form.Dxf = text
  parseDxfString(text)
}
watch(() => props.dxfText, (txt) => {
  if (txt && txt.length){ fileName.value = '(da DB)'; form.Dxf = txt; parseDxfString(txt) }
}, { immediate: true })

function parseDxfString(text){
  let dxf
  try { dxf = new DxfParser().parseSync(text) }
  catch(e){ alert('DXF non valido: '+e.message); return }

  // Tabelle & stili
  layersMap.value={}
  for(const L of dxf.tables?.layers ?? []) layersMap.value[L.name]={colorNumber:L.colorNumber??L.color??null,lineWeight:L.lineWeight??L.lineweight??-1}
  stylesMap.value={}
  for(const S of dxf.tables?.styles ?? []) stylesMap.value[S.name]={height:S.fixedHeight??S.textHeight??S.height??0}

  // Blocchi
  blocksMap.value=new Map()
  const rawBlocks=dxf.blocks ?? {}
  if(Array.isArray(rawBlocks)) rawBlocks.forEach(b=>blocksMap.value.set(b.name,{basePoint:b.basePoint??{x:0,y:0},entities:b.entities??[]}))
  else Object.entries(rawBlocks).forEach(([name,b])=>blocksMap.value.set(name,{basePoint:b.basePoint??{x:0,y:0},entities:b.entities??[]}))

  const out=[]
  const closedPolysRetino=[]
  const closedPolysManiglia=[]

  for(const ent of dxf.entities ?? []) expand(ent, matId(), null, out, closedPolysRetino, closedPolysManiglia)

  // Debug
  retinoLoops.value = closedPolysRetino
  manigliaLoops.value = closedPolysManiglia

  // Fills
  const fills=[]
  if (closedPolysManiglia.length){
    const segs=[]
    for (const pts of closedPolysManiglia){ segs.push(`M ${pts[0].x} ${pts[0].y}`); for(let i=1;i<pts.length;i++) segs.push(`L ${pts[i].x} ${pts[i].y}`); segs.push('Z') }
    fills.push({kind:'hatchPath', d:segs.join(' '), style:{ fill:FILL_MANIGLIA, fillRule:'evenodd' }})
  }
  if (closedPolysRetino.length){
    const segs=[]
    for (const pts of closedPolysRetino){ segs.push(`M ${pts[0].x} ${pts[0].y}`); for(let i=1;i<pts.length;i++) segs.push(`L ${pts[i].x} ${pts[i].y}`); segs.push('Z') }
    fills.push({kind:'hatchPath', d:segs.join(' '), style:{ fill:FILL_RETINO, fillRule:'evenodd' }})
  }

  drawables.value=[...fills, ...out]
}

/* ====== Espansione entità ====== */
function expand(ent, M, parentStyle, out, closedPolysRetino, closedPolysManiglia){
  if(!ent || ent.visible===false) return
  const style={ stroke: strokeOf(ent)||parentStyle?.stroke||'#000', strokeWidth: strokeWidthOf(ent) }

  // INSERT
  if(ent.type==='INSERT'){
    const name=ent.name||ent.block||ent.blockName
    const blk=blocksMap.value.get(name); if(!blk) return
    const pos=ent.position ?? {x:0,y:0}, rot=ent.rotation ?? 0, sx=ent.scaleX ?? 1, sy=ent.scaleY ?? 1
    let m=M; m=matMul(m, T(pos.x??0, -(pos.y??0))); if(rot) m=matMul(m,R(rot)); m=matMul(m,S(sx,sy))
    const bp=blk.basePoint ?? {x:0,y:0}; m=matMul(m, T(-(bp.x??0), +(bp.y??0)))
    for(const ch of blk.entities ?? []) expand(ch, m, style, out, closedPolysRetino, closedPolysManiglia)
    return
  }

  // LINE
  if(ent.type==='LINE' && ent.start && ent.end){
    out.push({ kind:'line', style,
      p1:applyM(M,{x:ent.start.x??0,y:-(ent.start.y??0)}),
      p2:applyM(M,{x:ent.end.x??0,  y:-(ent.end.y??0)}) })
    return
  }

  // LWPOLYLINE / POLYLINE
  if(ent.type==='LWPOLYLINE' || ent.type==='POLYLINE'){
    const raw=(ent.vertices||ent.points||[]).filter(Boolean)
    if(raw.length>=2){
      const pts=raw.map(v=>applyM(M,{x:v.x??0,y:-(v.y??0)}))
      const eps=1e-4, eq=(a,b)=>Math.abs(a-b)<=eps
      let isClosed = !!ent.closed
      if(!isClosed && pts.length>=3){
        const a=pts[0], b=pts[pts.length-1]
        if(eq(a.x,b.x) && eq(a.y,b.y)){ isClosed=true; pts.pop() }
      }
      out.push({kind:'poly',style,closed:isClosed,points:pts})
      if(isClosed){
        const layer=(ent.layer||'').toLowerCase()
        if(layer==='retino')   closedPolysRetino.push(pts)
        if(layer==='maniglia') closedPolysManiglia.push(pts)
      }
    }
    return
  }

  // CIRCLE
  if(ent.type==='CIRCLE' && ent.center){
    const c=applyM(M,{x:ent.center.x??0,y:-(ent.center.y??0)}); const r=(ent.radius??1)*((M[0]+M[3])/2)
    out.push({kind:'circle',style,c,r}); return
  }

  // 3DFACE / SOLID / TRACE
  if(ent.type==='3DFACE' || ent.type==='SOLID' || ent.type==='TRACE'){
    const verts=(ent.vertices||[]).filter(Boolean)
    if(verts.length>=3){
      const pts=verts.map(v=>applyM(M,{x:v.x??0,y:-(v.y??0)}))
      out.push({kind:'poly',style,closed:true,points:pts})
    }
    return
  }

  // TEXT
  if (ent.type === 'TEXT' && (ent.text || ent.string)) {
    let rawText = ent.text ?? ent.string ?? ''
    if (rawText === 'LRG') rawText = 'L600'

    let h = ent.hAlign ?? ent.horizontalAlignment ?? 0
    let v = ent.vAlign   ?? ent.verticalAlignment   ?? 0

    let anchorPoint = ent.position || ent.startPoint || { x: 0, y: 0 }
    let textLengthPx, lengthAdjust

    // Aligned/Fit → midpoint + larghezza forzata
    if ((h === 3 || h === 5) && ent.startPoint && ent.endPoint) {
      const mid = { x: (ent.startPoint.x + ent.endPoint.x) / 2,
                    y: (ent.startPoint.y + ent.endPoint.y) / 2 }
      anchorPoint = mid
      const p1 = applyM(M, { x: ent.startPoint.x, y: -(ent.startPoint.y) })
      const p2 = applyM(M, { x: ent.endPoint.x,   y: -(ent.endPoint.y)   })
      textLengthPx = Math.hypot(p2.x - p1.x, p2.y - p1.y)
      lengthAdjust = 'spacingAndGlyphs'
    } else if (ent.alignmentPoint) {
      anchorPoint = ent.alignmentPoint
      if (h === 0) h = 1     // infer Center
      if (v === 0) v = 3     // infer Top
    }

    const pos0 = applyM(M, { x: anchorPoint.x ?? 0, y: -(anchorPoint.y ?? 0) })
    const size = textHeightFor(ent, M)
    const angle = textAngle(ent, M)
    const anchor = mapTextAnchor(h)
    const baseline = mapTextBaseline(v)

    let dy = 0
    if (baseline === 'text-before-edge') dy = size * topTweak.value
    else if (baseline === 'text-after-edge') dy = size * bottomTweak.value

    const pos = { x: pos0.x, y: pos0.y + dy }

    out.push({
      kind: 'text',
      style,
      text: rawText,
      pos,
      size,
      angle,
      anchor,
      baseline,
      textLength: textLengthPx,
      lengthAdjust
    })
    return
  }

  // MTEXT
  if(ent.type==='MTEXT' && ent.text){
    let text=(ent.text||'').replace(/\\P/g,'\n').replace(/\\~|\\A\d|\\f[^;]+;|\\H[^;]+;|\\Q[^;]+;|\\W[^;]+;/g,'')
    text = text.replace(/\bLRG\b/g,'L600')
    const pos=applyM(M,{x:ent.insert?.x ?? 0, y:-(ent.insert?.y ?? 0)})
    const size=mtextHeightFor(ent,M), angle=textAngle(ent,M)
    const ap=ent.attachmentPoint ?? 1
    const anchor=mapMTextAnchor(ap)
    const baseline=mapMTextBaseline(ap)
    const lines=text.split('\n')
    out.push({kind:'mtext',style,lines,pos,size,angle,anchor,baseline,lineSpacing:(ent.lineSpacingFactor||1.0)})
    return
  }
}

/* ====== BBox & viewBox (testi esclusi) ====== */
const allPts = computed(()=>{
  const a=[]
  for(const e of drawables.value){
    if(e.kind==='line'){ a.push(e.p1,e.p2) }
    else if(e.kind==='poly'){ a.push(...e.points) }
    else if(e.kind==='circle'){
      for(let i=0;i<16;i++){ const t=i/16*TWO_PI; a.push({x:e.c.x+e.r*Math.cos(t), y:e.c.y+e.r*Math.sin(t)}) }
    }
    else if(e.kind==='hatchPath'){
      const nums=e.d.match(/-?\d+(\.\d+)?/g)?.map(Number)||[]
      for(let i=0;i<nums.length;i+=2) if(Number.isFinite(nums[i])&&Number.isFinite(nums[i+1])) a.push({x:nums[i],y:nums[i+1]})
    }
  }
  return a.length?a:[{x:0,y:0}]
})
const bbox = computed(()=>{
  const xs=allPts.value.map(p=>p.x), ys=allPts.value.map(p=>p.y)
  const minX=Math.min(...xs), maxX=Math.max(...xs), minY=Math.min(...ys), maxY=Math.max(...ys)
  const w=maxX-minX, h=maxY-minY, cx=(minX+maxX)/2, cy=(minY+maxY)/2
  return {w,h,cx,cy}
})
const padding = 50
const viewBox = computed(()=>`${-bbox.value.w/2-padding} ${-bbox.value.h/2-padding} ${Math.max(1,bbox.value.w)+padding*2} ${Math.max(1,bbox.value.h)+padding*2}`)
const centerTransform = computed(()=>`translate(${-bbox.value.cx}, ${-bbox.value.cy})`)

/* ====== Helpers template ====== */
const toPoints=pts=>pts.map(p=>`${p.x},${p.y}`).join(' ')

/* ====== Azioni salvataggio ====== */
function salva() {
  if (!form.Dxf && !form.dxf_file) {
    alert('Carica un file DXF o incolla il contenuto prima di salvare.');
    return;
  }
  if (isEdit.value) {
    form.put(route('disegni.update', form.IdRigaDXF), { preserveScroll: true })
  } else {
    form.post(route('disegni.store'), { preserveScroll: true })
  }
}

// opzionale: download locale del DXF renderizzato
function downloadDxf() {
  const blob = new Blob([form.Dxf || ''], { type: 'text/plain;charset=utf-8' })
  const url = URL.createObjectURL(blob)
  const a = document.createElement('a')
  a.href = url
  a.download = (form.Codice || 'disegno') + '.dxf'
  a.click()
  URL.revokeObjectURL(url)
}

/* ====== Init ====== */
onMounted(() => {
  if (props.dxfText) {
    parseDxfString(props.dxfText)
    fileName.value = '(da DB)'
  }
})
</script>

<template>
  <Head :title="title" />
  <div class="p-6 space-y-6">
    <h1 class="text-xl font-semibold">{{ title }}</h1>

    <!-- Metadati + upload -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div>
        <label class="block text-sm font-medium mb-1">Codice</label>
        <input v-model.trim="form.Codice" type="text" class="w-full border rounded p-2" />
      </div>
      <div>
        <label class="block text-sm font-medium mb-1">Descrizione</label>
        <input v-model.trim="form.Descrizione" type="text" class="w-full border rounded p-2" />
      </div>
      <div>
        <label class="block text-sm font-medium mb-1">LRG</label>
        <input v-model.trim="form.LRG" type="text" class="w-full border rounded p-2" />
      </div>
      <div>
        <label class="block text-sm font-medium mb-1">ALT</label>
        <input v-model.trim="form.ALT" type="text" class="w-full border rounded p-2" />
      </div>
    </div>

    <div class="flex flex-wrap items-center gap-3">
      <input type="file" accept=".dxf" @change="onFile" class="border p-2 rounded" />
      <button type="button" class="px-3 py-2 rounded bg-blue-600 text-white" @click="salva">
        {{ isEdit ? 'Aggiorna' : 'Salva nuovo' }}
      </button>
      <button type="button" class="px-3 py-2 rounded border" @click="downloadDxf">Scarica DXF</button>

      <label class="text-sm ml-4"><input type="checkbox" v-model="showFill" /> Mostra sfondi</label>
      <label class="text-sm"><input type="checkbox" v-model="showRetinoDebug" /> Debug retino</label>
      <label class="text-sm"><input type="checkbox" v-model="showTextDebug" /> Debug testo</label>

      <div class="flex items-center gap-2 text-sm">
        <span>Top</span>
        <input type="number" step="0.01" v-model.number="topTweak" class="border rounded px-2 py-1 w-20">
        <span>Bottom</span>
        <input type="number" step="0.01" v-model.number="bottomTweak" class="border rounded px-2 py-1 w-20">
      </div>

      <span v-if="fileName" class="text-sm text-gray-500">File: {{ fileName }}</span>
      <span v-if="form.IdRigaDXF" class="text-sm text-gray-500">Id: {{ form.IdRigaDXF }}</span>
    </div>

    <!-- Viewer fisso 200×300, sfondo trasparente -->
    <div v-if="drawables.length" class="border rounded" style="width:200px; height:300px;">
      <svg :viewBox="viewBox" preserveAspectRatio="xMidYMid meet" width="200" height="300" class="block" style="max-width:none;">
        <g :transform="centerTransform">
          <!-- SFONDI (sotto a tutto) -->
          <template v-if="showFill">
            <template v-for="(e,i) in drawables" :key="'f'+i">
              <path v-if="e.kind==='hatchPath'" :d="e.d" :fill="e.style.fill" fill-rule="evenodd" fill-opacity="0.6" stroke="none" />
            </template>
          </template>

          <!-- DEBUG retino -->
          <g v-if="showRetinoDebug">
            <template v-for="(loop,idx) in retinoLoops" :key="'dbg'+idx">
              <polyline :points="toPoints(loop)" :stroke="idx===0 ? '#0b3d91' : '#d97706'" :stroke-width="1" fill="none" />
              <line :x1="loop[loop.length-1].x" :y1="loop[loop.length-1].y" :x2="loop[0].x" :y2="loop[0].y"
                    :stroke="idx===0 ? '#0b3d91' : '#d97706'" stroke-dasharray="2 2" />
              <circle v-for="(p,i) in loop" :key="'pt'+idx+'-'+i" :cx="p.x" :cy="p.y" r="0.8" fill="#ef4444" />
            </template>
          </g>

          <!-- DEBUG testo: croce sugli anchor -->
          <g v-if="showTextDebug">
            <template v-for="(e,i) in drawables" :key="'tdbg'+i">
              <g v-if="e.kind==='text'">
                <line :x1="e.pos.x-3" :y1="e.pos.y" :x2="e.pos.x+3" :y2="e.pos.y" stroke="#d00" stroke-width="0.8"/>
                <line :x1="e.pos.x" :y1="e.pos.y-3" :x2="e.pos.x" :y2="e.pos.y+3" stroke="#d00" stroke-width="0.8"/>
              </g>
            </template>
          </g>

          <!-- GEOMETRIE sopra -->
          <template v-for="(e,i) in drawables" :key="'g'+i">
            <line v-if="e.kind==='line'" :x1="e.p1.x" :y1="e.p1.y" :x2="e.p2.x" :y2="e.p2.y"
                  :stroke="e.style.stroke" :stroke-width="e.style.strokeWidth" />
            <polygon v-else-if="e.kind==='poly' && e.closed" :points="toPoints(e.points)"
                     :stroke="e.style.stroke" :stroke-width="e.style.strokeWidth" fill="none" />
            <polyline v-else-if="e.kind==='poly' && !e.closed" :points="toPoints(e.points)"
                      :stroke="e.style.stroke" :stroke-width="e.style.strokeWidth" fill="none" />
            <circle v-else-if="e.kind==='circle'" :cx="e.c.x" :cy="e.c.y" :r="e.r"
                    :stroke="e.style.stroke" :stroke-width="e.style.strokeWidth" fill="none" />
            <text v-else-if="e.kind==='text'"
                  :x="e.pos.x" :y="e.pos.y"
                  :font-size="e.size"
                  :fill="e.style.stroke"
                  :text-anchor="e.anchor"
                  :dominant-baseline="e.baseline"
                  :textLength="e.textLength"
                  :lengthAdjust="e.lengthAdjust"
                  :transform="`rotate(${e.angle || 0}, ${e.pos.x}, ${e.pos.y})`">
              {{ e.text }}
            </text>
            <text v-else-if="e.kind==='mtext'"
                  :x="e.pos.x" :y="e.pos.y"
                  :font-size="e.size"
                  :fill="e.style.stroke"
                  :text-anchor="e.anchor"
                  :dominant-baseline="e.baseline"
                  :transform="`rotate(${e.angle || 0}, ${e.pos.x}, ${e.pos.y})`">
              <tspan v-for="(ln,idx) in e.lines" :key="idx" :x="e.pos.x" :dy="idx===0?0:e.size * (e.lineSpacing || 1.0)">{{ ln }}</tspan>
            </text>
          </template>
        </g>
      </svg>
    </div>

    <div v-else class="text-gray-500">Carica un file DXF oppure apri con un IdRigaDXF nell’URL.</div>
  </div>
</template>
