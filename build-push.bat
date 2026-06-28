@echo off
setlocal enabledelayedexpansion

REM ==================================================
REM  build-push.bat - npm run build + add/commit/push
REM  Uso:
REM    build-push.bat "messaggio del commit"
REM  Se non passi il messaggio, te lo chiede.
REM ==================================================

REM Vai nella cartella del progetto (quella dove si trova il .bat)
cd /d "%~dp0"

REM Messaggio: dai parametri oppure chiesto a video
set "MSG=%~1"
if "%MSG%"=="" (
    set /p MSG=Messaggio di commit: 
)
if "%MSG%"=="" (
    echo.
    echo [ANNULLATO] Nessun messaggio di commit inserito.
    pause
    exit /b 1
)

echo.
echo === npm run build ===
call npm run build
if errorlevel 1 (
    echo.
    echo [ERRORE] Build del frontend fallito. Push annullato.
    echo          Controlla gli errori qui sopra.
    echo.
    pause
    exit /b 1
)

echo.
echo === git add . ===
git add .
if errorlevel 1 goto :errore

echo.
echo === git commit ===
git commit -m "%MSG%"
if errorlevel 1 (
    echo.
    echo [INFO] Niente da committare, oppure commit non riuscito.
    echo        Proseguo comunque con il push...
)

echo.
echo === git push origin main ===
git push origin main
if errorlevel 1 goto :errore

echo.
echo === FATTO ===
echo Build + push completati con successo.
echo.
pause
exit /b 0

:errore
echo.
echo [ERRORE] Si e' verificato un problema. Controlla i messaggi qui sopra.
echo.
pause
exit /b 1
