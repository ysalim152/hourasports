#!/bin/bash

# Automated Deployment Script for HoraSports Web Application
# This script sets up a LAMP stack (Linux, Apache, MySQL, PHP) and deploys the application

set -e  # Exit on any error

echo "🚀 Starting deployment of HoraSports Web Application..."

# Variables (customize as needed)
APP_NAME="horasports"
WEB_ROOT="/var/www/html/$APP_NAME"
DB_NAME="association_db"
DB_USER="horasports_user"
DB_PASS="secure_password_123"  # Change this!
REPO_URL="https://github.com/yourusername/horasports.git"  # Replace with actual repo URL

# Update system
echo "📦 Updating system packages..."
sudo apt update && sudo apt upgrade -y

# Install Apache, PHP, MySQL
echo "🛠️ Installing Apache, PHP 8, and MariaDB..."
sudo apt install -y apache2 php8.1 php8.1-mysql php8.1-mbstring php8.1-xml php8.1-curl php8.1-zip php8.1-gd mariadb-server

# Start and enable services
sudo systemctl start apache2
sudo systemctl enable apache2
sudo systemctl start mariadb
sudo systemctl enable mariadb

# Secure MySQL installation (automated)
echo "🔒 Securing MariaDB..."
sudo mysql -e "UPDATE mysql.user SET Password=PASSWORD('$DB_PASS') WHERE User='root';"
sudo mysql -e "DELETE FROM mysql.user WHERE User='';"
sudo mysql -e "DELETE FROM mysql.user WHERE User='root' AND Host NOT IN ('localhost', '127.0.0.1', '::1');"
sudo mysql -e "DROP DATABASE IF EXISTS test;"
sudo mysql -e "FLUSH PRIVILEGES;"

# Create database and user
echo "🗄️ Setting up database..."
sudo mysql -u root -p$DB_PASS -e "CREATE DATABASE IF NOT EXISTS $DB_NAME CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
sudo mysql -u root -p$DB_PASS -e "CREATE USER IF NOT EXISTS '$DB_USER'@'localhost' IDENTIFIED BY '$DB_PASS';"
sudo mysql -u root -p$DB_PASS -e "GRANT ALL PRIVILEGES ON $DB_NAME.* TO '$DB_USER'@'localhost';"
sudo mysql -u root -p$DB_PASS -e "FLUSH PRIVILEGES;"

# Clone or copy the repository
echo "📥 Cloning repository..."
if [ -d "$WEB_ROOT" ]; then
    sudo rm -rf $WEB_ROOT
fi
sudo git clone $REPO_URL $WEB_ROOT

# Import database schema
echo "📊 Importing database schema..."
sudo mysql -u $DB_USER -p$DB_PASS $DB_NAME < $WEB_ROOT/database/association_db.sql
sudo mysql -u $DB_USER -p$DB_PASS $DB_NAME < $WEB_ROOT/database/migration_actualites_v2.sql

# Configure Apache
echo "🌐 Configuring Apache..."
sudo a2enmod rewrite
sudo tee /etc/apache2/sites-available/$APP_NAME.conf > /dev/null <<EOF
<VirtualHost *:80>
    ServerName localhost
    DocumentRoot $WEB_ROOT

    <Directory $WEB_ROOT>
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog \${APACHE_LOG_DIR}/$APP_NAME_error.log
    CustomLog \${APACHE_LOG_DIR}/$APP_NAME_access.log combined
</VirtualHost>
EOF

sudo a2ensite $APP_NAME.conf
sudo a2dissite 000-default.conf
sudo systemctl reload apache2

# Set permissions
echo "🔑 Setting file permissions..."
sudo chown -R www-data:www-data $WEB_ROOT
sudo chmod -R 755 $WEB_ROOT
sudo chmod -R 777 $WEB_ROOT/uploads  # If uploads directory exists

# Update config/db.php with database credentials
echo "⚙️ Updating database configuration..."
sudo sed -i "s/'DB_NAME', '.*'/'DB_NAME', '$DB_NAME'/g" $WEB_ROOT/config/db.php
sudo sed -i "s/'DB_USER', '.*'/'DB_USER', '$DB_USER'/g" $WEB_ROOT/config/db.php
sudo sed -i "s/'DB_PASS', '.*'/'DB_PASS', '$DB_PASS'/g" $WEB_ROOT/config/db.php

# Restart services
echo "🔄 Restarting services..."
sudo systemctl restart apache2
sudo systemctl restart mariadb

# Test deployment
echo "🧪 Testing deployment..."
if curl -s http://localhost/ | grep -q "HoraSports"; then
    echo "✅ Deployment successful! Access your application at http://localhost/"
else
    echo "❌ Deployment may have issues. Check logs."
fi

echo "🎉 Deployment complete!"