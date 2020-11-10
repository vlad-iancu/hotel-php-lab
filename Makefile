install-mods:
	sudo apt-get install libapache2-mod-headers
	sudo a2enmod headers
	sudo apt-get install libapache2-mod-mpm-itk
	sudo a2enmod mpm-itk
	sudo apt-get install libapache2-mod-php7.4
	sudo a2enmod php7.4
	sudo apt-get install php-mysql
	sudo apt-get install libapache2-mod-ssl
	sudo a2enmod ssl
	sudo a2enmod rewrite
	sudo a2enmod security
	sudo systemctl restart apache2
setup:
	cp config/hotel-api.conf /etc/apache2/sites-available/hotel-api.conf
	sudo mkdir /var/www/hotel
	sudo ln -s . /var/www/hotel/scripts
	sudo ln -s ./logs /var/www/hotel/logs
	sudo ln -s /etc/apache2/sites-available/hotel-api.conf /etc/apache2/sites-enabled/hotel-api.conf
	sudo ufw allow in 5222
	sudo ufw allow in "Apache Full"
	sudo ufw enable
	sudo systemctl restart apache2
	


