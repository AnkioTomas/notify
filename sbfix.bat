@echo off
rem ------------------------------------------------------------
rem  rebuild-gitmodules.bat
rem  重新生成 .gitmodules（Windows 批处理版本）
rem ------------------------------------------------------------
setlocal ENABLEDELAYEDEXPANSION

rem 1) 先把 .gitmodules 清空
echo.> .gitmodules

rem 2) 遍历 .git/config 里所有 submodule.*.url
for /F "tokens=1,*" %%A in ('
    git config --file .git/config --get-regexp "submodule\..*\.url$"
') do (
    set "key=%%A"
    set "url=%%B"

    rem 提取子模块名：submodule.<name>.url  →  <name>
    for /F "tokens=2 delims=." %%n in ("!key!") do set "name=%%n"

    rem 可选：读取 path（有些仓库名=路径，这里仍然照读一次）
    set "spath="
    for /F "delims=" %%p in ('
        git config --file .git/config --get "submodule.!name!.path"
    ') do set "spath=%%p"

    rem 若未取到 path，就退回用 name
    if not defined spath set "spath=!name!"

    >> .gitmodules echo [submodule "!name!"]
    >> .gitmodules echo     path = !spath!
    >> .gitmodules echo     url = !url!
)

echo .gitmodules 文件已成功重建！
endlocal
