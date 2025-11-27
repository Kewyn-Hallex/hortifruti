# Deploy no Sevalla

Este documento contém instruções para fazer deploy da aplicação Laravel no Sevalla.

## Pré-requisitos

1. Conta no Sevalla (https://sevalla.com)
2. Repositório Git configurado (GitHub, GitLab ou Bitbucket)
3. Banco de dados configurado (MySQL, PostgreSQL, etc.)

## Passos para Deploy

### 1. Criar Aplicação no Sevalla

1. Acesse o painel do Sevalla
2. Clique em "Applications" > "New Application"
3. **IMPORTANTE - Configurar Build Method**:
   - Se o Sevalla detectar automaticamente o Nixpacks e der erro:
     - Vá em "Settings" > "Build Configuration" da aplicação
     - Selecione "Dockerfile" como método de build
     - Ou escolha "Docker Image" como tipo de fonte
   - O projeto já possui um `Dockerfile` otimizado que será usado automaticamente
4. Configure:
   - Nome da aplicação
   - Região
   - Tamanho do pod
5. Clique em "Create"

**Nota**: Se você encontrar erros relacionados ao Nixpacks (como `nodejs_24` não encontrado), certifique-se de que o método de build está configurado para usar o Dockerfile no painel do Sevalla.

### 2. Configurar Variáveis de Ambiente

No painel do Sevalla, adicione as seguintes variáveis de ambiente na seção "Environment Variables":

#### Obrigatórias:
- `APP_NAME`: Nome da aplicação
- `APP_ENV`: `production`
- `APP_KEY`: Chave da aplicação (gere com `php artisan key:generate`)
- `APP_DEBUG`: `false`
- `APP_URL`: URL da sua aplicação no Sevalla

#### Banco de Dados:
- `DB_CONNECTION`: `mysql` ou `pgsql`
- `DB_HOST`: Host do banco de dados
- `DB_PORT`: Porta do banco (3306 para MySQL, 5432 para PostgreSQL)
- `DB_DATABASE`: Nome do banco de dados
- `DB_USERNAME`: Usuário do banco
- `DB_PASSWORD`: Senha do banco

#### Opcionais (mas recomendadas):
- `SESSION_DRIVER`: `database` ou `redis`
- `CACHE_STORE`: `database` ou `redis`
- `QUEUE_CONNECTION`: `database` ou `redis`
- `LOG_CHANNEL`: `stack`
- `LOG_LEVEL`: `error` ou `warning`

#### Para executar migrações automaticamente no deploy:
- `RUN_MIGRATIONS`: `true` (opcional, apenas se quiser migrações automáticas)

### 3. Configurar Health Check

1. No painel do Sevalla, vá em "Settings" da aplicação
2. Configure o Health Check:
   - Path: `/up`
   - Port: `80`

### 4. Build e Deploy

O Sevalla irá automaticamente:
1. Fazer build da imagem Docker usando o `Dockerfile`
2. Executar o script `docker-entrypoint.sh` na inicialização
3. Iniciar o servidor Apache

### 5. Primeiro Deploy

Para o primeiro deploy, você precisará:

1. **Gerar a APP_KEY**:
   ```bash
   php artisan key:generate
   ```
   Copie a chave gerada e adicione como variável de ambiente `APP_KEY` no Sevalla.

2. **Executar migrações**:
   - Opção 1: Configure `RUN_MIGRATIONS=true` nas variáveis de ambiente
   - Opção 2: Execute manualmente via console do Sevalla:
     ```bash
     php artisan migrate --force
     ```

3. **Criar link simbólico do storage**:
   ```bash
   php artisan storage:link
   ```

## Estrutura do Dockerfile

O Dockerfile usa multi-stage build para otimizar o tamanho da imagem:

1. **Stage 1 (build-assets)**: Compila os assets do frontend (Vue, Tailwind)
2. **Stage 2 (composer-deps)**: Instala dependências do PHP
3. **Stage 3 (final)**: Imagem final de produção com Apache

## Otimizações Aplicadas

- Cache de configuração, rotas e views
- Autoloader otimizado do Composer
- Assets compilados em build stage separado
- Permissões corretas para storage e cache

## Troubleshooting

### Erro do Nixpacks (nodejs_24 não encontrado)
Se você encontrar erros como `error: undefined variable 'nodejs_24'`:
1. Vá em "Settings" > "Build Configuration" da aplicação no Sevalla
2. Altere o método de build para "Dockerfile"
3. Salve as configurações
4. Faça um novo deploy

O projeto está configurado para usar Dockerfile, que é mais confiável e otimizado.

### Erro de permissões
Se houver erros de permissão, verifique se as pastas `storage` e `bootstrap/cache` têm permissão 775.

### Erro de banco de dados
Verifique se as variáveis de ambiente do banco estão corretas e se o banco está acessível do Sevalla.

### Assets não carregam
Certifique-se de que o build dos assets foi executado corretamente. Verifique o log do build no Sevalla.

### Cache não funciona
Execute manualmente no console do Sevalla:
```bash
php artisan config:clear
php artisan cache:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## Auto-deploy

Para habilitar auto-deploy a cada commit:

1. Vá em "Settings" da aplicação
2. Ative "Auto-deploy"
3. Selecione o branch (geralmente `main` ou `master`)

## Monitoramento

- Acompanhe os logs em "Logs" no painel do Sevalla
- Configure notificações em "User settings" > "Notifications"
- Monitore o histórico de deploys em "Deployments"

