#!/usr/bin/env bash
# команды
# tmux new -s mlservice
# tmux new -d -s mlservice './start.sh'
# tmux attach -t mlservice
# tmux ls
# source venv/bin/activate
# lsof -iTCP:8000 -sTCP:LISTEN
# pkill -f serve_model_flask.py
# chmod +x start.sh
# ./start.sh

# Последовательно выполняем:
# cd /home/c/cz06737izol/crypto/public_html/ml        // 1. Переход в нужную директорию
# tmux kill-session -t mlservice 2>/dev/null || true  // 2. Останавливаем старую сессию tmux, если она есть
# chmod +x start.sh                                   // 3. Делаем start.sh исполняемым
# tmux new -d -s mlservice './start.sh'               // 4. Запускаем новую сессию tmux в фоне

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