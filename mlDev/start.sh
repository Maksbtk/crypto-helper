#!/usr/bin/env bash

# Переходим в директорию скрипта
#  / tmux new -s mldevservice / tmux attach -t mldevservice / tmux ls / source venv/bin/activate / lsof -iTCP:8001 -sTCP:LISTEN
# pkill -f serve_model_flask.py / chmod +x start.sh / ./start.sh

# команды
# tmux new -s mldevservice
# tmux new -d -s mldevservice './start.sh'
# tmux attach -t mldevservice
# tmux ls
# source venv/bin/activate
# lsof -iTCP:8000 -sTCP:LISTEN
# pkill -f serve_model_flask.py
# chmod +x start.sh
# ./start.sh

# Последовательно выполняем:
# cd /home/c/cz06737izol/crypto/public_html/mlDev       // 1. Переход в нужную директорию
# tmux kill-session -t mldevservice 2>/dev/null || true  // 2. Останавливаем старую сессию tmux, если она есть
# chmod +x start.sh                                   // 3. Делаем start.sh исполняемым
# tmux new -d -s mldevservice './start.sh'               // 4. Запускаем новую сессию tmux в фоне


cd "$(dirname "$0")"

# Ограничиваем потоки (BLAS/OMP)
export OMP_NUM_THREADS=1
export OPENBLAS_NUM_THREADS=1
export MKL_NUM_THREADS=1
export VECLIB_MAXIMUM_THREADS=1

# Активируем venv, если есть
if [ -f "venv/bin/activate" ]; then
  source venv/bin/activate
fi

# Запускаем
exec python serve_model_flask.py