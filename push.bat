@echo off
setlocal enabledelayedexpansion

REM ============================================
REM  push.bat - add + commit + push su main
REM  Uso:
REM    push.bat "messaggio del commit"
REM  Se non passi il messaggio, te lo chiede.
REM ============================================

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
echo Push completato con successo.
echo.
pause
exit /b 0

:errore
echo.
echo [ERRORE] Si e' verificato un problema. Controlla i messaggi qui sopra.
echo.
pause
exit /b 1
