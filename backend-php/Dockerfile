# Dockerfile para Plataforma WhatsApp AI - Backend PHP
FROM php:8.1-apache

# Definir variáveis de ambiente
ENV APACHE_DOCUMENT_ROOT /var/www/html
ENV DEBIAN_FRONTEND=noninteractive

# Instalar dependências do sistema e de compilação
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    libicu-dev \
    libonig-dev \
    libxml2-dev \
    libcurl4-openssl-dev \
    pkg-config \
    git \
    build-essential \
    libsqlite3-dev \
    # Dependências para curl, se necessário
    # libssl-dev \
    # libnghttp2-dev \
    && rm -rf /var/lib/apt/lists/*

# Instalar e habilitar extensões PHP manualmente para maior robustez
# Isso contorna o problema de 'cp: cannot stat modules/*' ao gerenciar a compilação e habilitação.

# Configurar e instalar GD
RUN docker-php-ext-configure gd --with-freetype --with-jpeg
RUN docker-php-ext-install gd

# Instalar PDO
RUN docker-php-ext-install pdo

# Instalar PDO_MySQL
RUN docker-php-ext-install pdo_mysql

# Instalar MySQLi
RUN docker-php-ext-install mysqli

# Instalar Zip
RUN docker-php-ext-install zip

# Instalar Intl
RUN docker-php-ext-install intl

# Instalar Mbstring
RUN docker-php-ext-install mbstring

# Instalar XML
RUN docker-php-ext-install xml

# Instalar JSON
RUN docker-php-ext-install json

# Instalar CURL
RUN docker-php-ext-install curl

# Habilitar módulos do Apache
RUN a2enmod rewrite headers ssl

# Configurar Apache
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Configurar PHP
RUN echo "upload_max_filesize = 10M" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "post_max_size = 10M" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "memory_limit = 256M" >> /usr/local/etc/php/conf.d/memory.ini \
    && echo "max_execution_time = 300" >> /usr/local/etc/php/conf.d/execution.ini

# Criar diretório de trabalho
WORKDIR /var/www/html

# Copiar arquivos da aplicação
COPY . /var/www/html/

# Configurar permissões
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod 644 /var/www/html/config/database.php

# Criar script de inicialização
RUN echo '#!/bin/bash\n\
# Aguardar banco de dados estar disponível\n\
echo "Aguardando banco de dados..."\n\
while ! mysqladmin ping -h"$DB_HOST" -P"$DB_PORT" --silent; do\n\
    sleep 1\n\
done\n\
echo "Banco de dados disponível!"\n\
\n\
# Executar migrações se necessário\n\
if [ "$RUN_MIGRATIONS" = "true" ]; then\n\
    echo "Executando migrações..."\n\
    mysql -h"$DB_HOST" -P"$DB_PORT" -u"$DB_USER" -p"$DB_PASSWORD" "$DB_NAME" < /var/www/html/database/schema.sql\n\
    echo "Migrações concluídas!"\n\
fi\n\
\n\
# Iniciar Apache\n\
apache2-foreground' > /usr/local/bin/start.sh

RUN chmod +x /usr/local/bin/start.sh

# Expor porta 80
EXPOSE 80

# Comando de inicialização
CMD ["/usr/local/bin/start.sh"]

# Labels para metadados
LABEL maintainer="Manus AI"
LABEL description="Plataforma WhatsApp AI - Backend PHP"
LABEL version="1.0.0"
