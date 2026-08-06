@echo off
chcp 65001 >nul
setlocal

echo ============================================
echo   Video Analysis - Local Server
echo ============================================
echo.

set PHP_PATH=E:\internet\workbuddy\jiexi\php\php.exe
set PROJECT_DIR=E:\internet\workbuddy\jiexi\qushuiyin-jiexi
set A_BOGUS_PORT=9876

echo [1/2] 启动抖音签名服务 (端口 %A_BOGUS_PORT%)...
start /B node "%PROJECT_DIR%\scripts\a_bogus_server.js"
echo   签名服务已启动

echo [2/2] 启动 PHP 解析服务 (端口 8000)...
echo   网页地址: http://localhost:8000
echo   API 地址: http://localhost:8000/api/v1
echo.
"%PHP_PATH%" -S localhost:8000 -t "%PROJECT_DIR%\public" "%PROJECT_DIR%\public\router.php"

pause
