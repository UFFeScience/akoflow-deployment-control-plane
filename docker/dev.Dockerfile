FROM php:8.5-apache

RUN apt-get update && apt-get install -y \
    git \
    curl \
    unzip \
    zip \
    libpq-dev \
    libzip-dev \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    python3 \
    python3-pip \
    python3-venv \
    openssh-client \
    && install -m 0755 -d /etc/apt/keyrings \
    && curl -fsSL https://download.docker.com/linux/debian/gpg -o /etc/apt/keyrings/docker.asc \
    && chmod a+r /etc/apt/keyrings/docker.asc \
    && echo "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.asc] https://download.docker.com/linux/debian $(. /etc/os-release && echo \"$VERSION_CODENAME\") stable" > /etc/apt/sources.list.d/docker.list \
    && apt-get update && apt-get install -y docker-ce-cli \
    && docker-php-ext-install \
        pdo \
        pdo_pgsql \
        mbstring \
        bcmath \
        exif \
        pcntl \
        zip \
    && pecl install xdebug \
    && docker-php-ext-enable xdebug \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Install Ansible via pip (isolated to avoid OS package conflicts)
RUN python3 -m venv /opt/ansible-venv \
    && /opt/ansible-venv/bin/pip install --upgrade pip \
    && /opt/ansible-venv/bin/pip install ansible \
    && ln -s /opt/ansible-venv/bin/ansible /usr/local/bin/ansible \
    && ln -s /opt/ansible-venv/bin/ansible-playbook /usr/local/bin/ansible-playbook \
    && ln -s /opt/ansible-venv/bin/ansible-galaxy /usr/local/bin/ansible-galaxy \
    && ansible --version

# Install Terraform
RUN TERRAFORM_VERSION=1.14.7 && \
    curl -fsSL https://releases.hashicorp.com/terraform/${TERRAFORM_VERSION}/terraform_${TERRAFORM_VERSION}_linux_amd64.zip -o /tmp/terraform.zip && \
    unzip /tmp/terraform.zip -d /usr/local/bin && \
    rm /tmp/terraform.zip && \
    terraform version

RUN a2enmod rewrite

ENV APACHE_DOCUMENT_ROOT /var/www/html/public

RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' \
    /etc/apache2/sites-available/*.conf \
    /etc/apache2/apache2.conf \
    /etc/apache2/conf-available/*.conf

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html