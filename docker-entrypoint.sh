#!/bin/bash
set -e

# Aguardar até que o banco de dados esteja disponível (se necessário)
# Você pode adicionar lógica aqui para verificar conexão com o banco

# Executar migrações apenas se a variável RUN_MIGRATIONS estiver definida
if [ "$RUN_MIGRATIONS" = "true" ]; then
    echo "Executando migrações..."
    php artisan migrate --force
fi

# Limpar e otimizar cache
echo "Otimizando cache..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Criar link simbólico para storage se não existir
if [ ! -L public/storage ]; then
    php artisan storage:link || true
fi

# Executar o comando passado como argumento
exec "$@"

