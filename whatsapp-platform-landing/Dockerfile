# Dockerfile para o Site de Divulgação (Frontend React)
FROM node:20-alpine

# Definir diretório de trabalho
WORKDIR /app

# Copiar package.json e pnpm-lock.yaml
COPY package.json pnpm-lock.yaml ./

# Instalar dependências com pnpm
RUN pnpm install --frozen-lockfile

# Copiar o restante do código da aplicação
COPY . .

# Construir a aplicação React
RUN pnpm run build

# Servir a aplicação com Nginx (opcional, EasyPanel pode servir diretamente)
# FROM nginx:alpine
# COPY --from=builder /app/dist /usr/share/nginx/html
# EXPOSE 80
# CMD ["nginx", "-g", "daemon off;"]

# Para EasyPanel, o build é suficiente, ele serve os arquivos estáticos
# Se o EasyPanel não servir estáticos, pode-se usar um servidor como `serve`
RUN pnpm install -g serve

EXPOSE 5173
CMD ["serve", "-s", "dist", "-l", "5173"]
