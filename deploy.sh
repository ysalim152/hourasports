#!/bin/bash

# Automated Deployment Script for HoraSports Web Application
# This script sets up a LAMP stack (Linux, Apache, MySQL, PHP) and deploys the application

set -e  # Exit on any error

echo "🚀 Starting deployment of HoraSports Web Application..."

# Variables (customize as needed)
APP_NAME="horasports"
WEB_ROOT="/var/www/html/$APP_NAME"
DB_NAME="association_db"
DB_USER="horasports_user" # Do not use 'root' for the application
DB_PASS="CHANGEME_A_STRONG_PASSWORD"  # IMPORTANT: Change this to a strong, unique password
REPO_URL="https://github.com/yourusername/horasports.git"  # IMPORTANT: Replace with your actual repository URL

if [[ "$DB_PASS" == "CHANGEME_A_STRONG_PASSWORD" ]] || [[ "$REPO_URL" == "https://github.com/yourusername/horasports.git" ]]; then
    echo "❌ ERROR: Please edit the script and change DB_PASS and REPO_URL variables."
    exit 1
fi

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
export MYSQL_PWD=$DB_PASS # Use env var to avoid password in process list for root user setup
sudo mysql -u root -e "SET PASSWORD FOR 'root'@'localhost' = PASSWORD('$DB_PASS');"
sudo mysql -u root -e "DELETE FROM mysql.user WHERE User='';"
sudo mysql -u root -e "DELETE FROM mysql.user WHERE User='root' AND Host NOT IN ('localhost', '127.0.0.1', '::1');"
sudo mysql -u root -e "DROP DATABASE IF EXISTS test;"
sudo mysql -u root -e "FLUSH PRIVILEGES;"

# Create database and user
echo "🗄️ Setting up database..."
sudo mysql -u root -e "CREATE DATABASE IF NOT EXISTS $DB_NAME CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
sudo mysql -u root -e "CREATE USER IF NOT EXISTS '$DB_USER'@'localhost' IDENTIFIED BY '$DB_PASS';"
sudo mysql -u root -e "GRANT ALL PRIVILEGES ON $DB_NAME.* TO '$DB_USER'@'localhost';"
sudo mysql -u root -e "FLUSH PRIVILEGES;"
unset MYSQL_PWD # Unset the password variable

# Clone or copy the repository
echo "📥 Deploying application files..."
if [ -d "$WEB_ROOT" ]; then
    echo "Repository exists, pulling latest changes..."
    cd $WEB_ROOT
    sudo git pull
else
    echo "Cloning repository..."
    sudo git clone $REPO_URL $WEB_ROOT
fi

# Import database schema
echo "📊 Importing database schema..."
export MYSQL_PWD=$DB_PASS
sudo mysql -u $DB_USER $DB_NAME < $WEB_ROOT/database/association_db.sql
sudo mysql -u $DB_USER $DB_NAME < $WEB_ROOT/database/migration_actualites_v2.sql
unset MYSQL_PWD

# Configure Apache
echo "🌐 Configuring Apache..."
sudo a2enmod rewrite
cat <<EOF | sudo tee /etc/apache2/sites-available/$APP_NAME.conf > /dev/null
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
if [ -d "$WEB_ROOT/uploads" ]; then
    sudo chmod -R 775 $WEB_ROOT/uploads  # 777 is insecure, 775 is better if www-data is the group
fi

# Update config/db.php with database credentials
echo "⚙️ Updating database configuration... (This is brittle, consider using environment variables)"
sudo sed -i "s/\$dbName = getenv('DB_NAME') \?: '.*';/\$dbName = getenv('DB_NAME') \?: '$DB_NAME';/" $WEB_ROOT/config/db.php
sudo sed -i "s/\$dbUser = getenv('DB_USER') \?: '.*';/\$dbUser = getenv('DB_USER') \?: '$DB_USER';/" $WEB_ROOT/config/db.php
sudo sed -i "s/\$dbPass = getenv('DB_PASS') \?: '.*';/\$dbPass = getenv('DB_PASS') \?: '$DB_PASS';/" $WEB_ROOT/config/db.php

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