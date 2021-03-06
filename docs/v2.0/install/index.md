---
title: Installation - Linux Source 2.0-Beta4 from Git
---

# Installation FileSender 2.0 Beta4

_This documentation is under development. It was created by installing FileSender on a CentOS 7 and Debian 8 machine._

## Please help us with the FileSender one hour installation time guarantee

We've all used more time than we wanted on a first time install of a piece of open source software, just to see whether it does something interesting. We do not want FileSender to be like that.

This documentation is a work in progress and will be cleaned up as the software progresses from beta to full release quality. The documentation itself might also contain mistakes.

If you notice mistakes in this documentation, or if it took you more than an hour to install FileSender, please let us know on filesender-dev@filesender.org and help us improve the documentation for those that come after you!

## About this documentation

This is the installation documentation for installing the **FileSender 2.0-beta version Git snapshots** on Linux. See the [releases](https://github.com/filesender/filesender/releases) page for up to date information about recent releases. This guide is written for installation from source on the RedHat/CentOS or Debian platform but any Linux variant should work with some modifications (most notably about installing the required additional software packages).

### This documentation was tested with

* RedHat/CentOS (7)
* Debian (8, Jessie)
* Fedora

### Dependencies

* SimpleSamlPhp 1.14.16 or newer.
* Apache (or nginx) and PHP from your distribution.
* A PostgreSQL or MySQL database.
* A big filesystem (or cloud backed).

# Step 0 - Choose your options

For the Web server you can use either Apache or NGINX. For a database
you can use PostgreSQL or MySQL. There are multiple versions of the steps
for the Web server setup, one for each supported server.


# Step 1-apache - Install Apache and PHP

On RedHat/CentOS, run:

	yum install -y httpd mod_ssl php php-mbstring php-xml

On Debian, run:

	apt-get install -y apache2 php5 libapache2-mod-php5

# Step 1-nginx - Install NGINX and PHP

Its for Debian/Ubuntu use a modern Nginx (after v.0.8) and php-fpm (PHP 5.5.9 (fpm-fcgi)).

	sudo apt-get install nginx php5-fpm 



# Step 2 - Install the FileSender package

Install the Git package on RedHat/CentOS:

	yum install -y git

Or install the Git package on Debian:

	apt-get install -y git

Install the FileSender 2.0 beta branch from the GIT repository use the
following commands. Note that you can use beta4, beta5 etc if there
are more beta releases. See
[Releases](https://github.com/filesender/filesender/releases) for
information about recent releases.

	mkdir /opt/filesender/
	cd /opt/filesender/
	git clone https://github.com/filesender/filesender.git filesender-2.0
		cd filesender-2.0
		git checkout filesender-2.0-beta4
		cd ..
	ln -s filesender-2.0/ filesender

Initialise config file and set permissions right. Make the files, tmp and log directories writable by the web daemon user (`apache` on RedHat/CentOS, `www-data` on Debian), copy the config file in place from the template and allow the web daemon user to read the config.php configuration file:

On RedHat/CentOS/Debian, run:

	cd /opt/filesender/filesender
	cp config/config_sample.php config/config.php
	mkdir tmp files log
	chmod o-rwx tmp files log config/config.php

On RedHat/CentOS, run:

	chown apache:apache tmp files log
	chgrp apache config/config.php
	semanage fcontext -a -t httpd_sys_content_t '/opt/filesender(/.*)?'
	semanage fcontext -a -t httpd_sys_rw_content_t '/opt/filesender/(log|tmp|files)(/.*)?'
	setsebool -P httpd_can_sendmail on
	restorecon -R /opt/filesender

On Debian, run:

	chown www-data:www-data tmp files log
	chgrp www-data config/config.php

* **NOTE**: We ship the FileSender tarball with `config_sample.php` rather than `config.php` to make life easier when building RPMs and DEBs.
* **NOTE**: If you use NFS storage for user files on RedHat/CentOS, mount it with the following option: `context=system_u:object_r:httpd_sys_rw_content_t:s0`.
* **DO NOT** enable `httpd_use_nfs`. If you did so before, roll back using `setsebool -P httpd_use_nfs off`.

# Step 3 - Install and configure SimpleSAMLphp

SimpleSAMLphp helps you use nearly any authentication mechanism you can imagine. Following these instructions will set you up with a SimpleSAMLphp installation that uses Feide RnD's OpenIdP to authenticate users. When you move to a production service you probably want to change that to only support authentication sources of your choice.

[Download SimpleSamlPhp](https://simplesamlphp.org/download).
Other [(later or older) versions](https://simplesamlphp.org/archive) will probably work. For the FileSender 2.0 release we tested with version 1.14.13.

	cd /root
	mkdir filesender
	cd filesender
	yum -y install wget
	wget https://github.com/simplesamlphp/simplesamlphp/releases/download/v1.15.4/simplesamlphp-1.15.4.tar.gz

* **NOTE**: you will of course remember to check [the sha256 hash of the tar file](https://simplesamlphp.org/archive), right?

Extract it in a suitable directory and create symlink:

	cd /opt/filesender
	tar xvzf /root/filesender/simplesamlphp-1.15.4.tar.gz
	ln -s simplesamlphp-1.15.4/ simplesaml

* **SECURITY NOTE**: we only want *the user interface files* to be directly accessible for the world through the web server, not any of the other files. We will not extract the SimpleSAMLphp package in the `/var/www` directory (the standard Apache document root) but rather in a specific `/opt` tree. We'll point to the SimpleSAML web root with a web server alias.

To tailor your [SimpleSAMLphp](http://simplesamlphp.org/) installation to match your local site's needs please check its [installation and configuration documentation](http://simplesamlphp.org/docs). When connecting to an Identity provider make sure all the required attributes are sent by the identity provider. See the section on [IdP attributes](../admin/reference/#idp_attributes) in the Reference Manual for details.

* **NOTE**: It's outside the scope of this document to explain how to configure an authentication backend. The software has built-in support for [SAML](https://simplesamlphp.org/docs/stable/ldap:ldap), [LDAP](https://simplesamlphp.org/docs/stable/ldap:ldap), [Radius](https://simplesamlphp.org/docs/stable/radius:radius) and [many more](https://simplesamlphp.org/docs/stable/simplesamlphp-idp#section_2).

# Step 4-apache - Configure Apache

Create a configuration file for FileSender. This file is located in one of these locations:

* **/etc/httpd/conf.d/filesender.conf** (RedHat/CentOS)
* **/etc/apache2/sites-available/filesender.conf** (Debian)

The contents of the file must be as follows:

	Alias /simplesaml /opt/filesender/simplesaml/www
	<Directory "/opt/filesender/simplesaml/www">
		Options None
		AllowOverride None
		Require all granted
	</Directory>

	Alias /filesender /opt/filesender/filesender/www
	<Directory "/opt/filesender/filesender/">
		Options SymLinksIfOwnerMatch
		AllowOverride None
		Require all granted
	</Directory>

On RedHat/CentOS, run:

   	systemctl --now enable httpd

On Debian you must enable your configuration, run:

	a2enmod alias headers ssl
	a2ensite default-ssl filesender

# Step 4-nginx - Configure NGINX


Edit file /etc/nginx/nginx.conf

```
user www-data;
worker_processes 4;
pid /run/nginx.pid;
events {
        worker_connections 1024;
        use epoll;
}
http {
        sendfile on;
        tcp_nopush on;
        tcp_nodelay on;
        keepalive_timeout 65;
        types_hash_max_size 2048;
        include /etc/nginx/mime.types;
        default_type application/octet-stream;
        access_log /var/log/nginx/access.log;
        error_log /var/log/nginx/error.log;
        gzip on;
        gzip_disable "MSIE [1-6]\.(?!.*SV1)";
        include /etc/nginx/conf.d/*.conf;
        include /etc/nginx/sites-enabled/*;
}
```

Then setup your site file similar to the below. This
would be in a file such as /etc/nginx/sites-enabled/filesender.example.com.

```
server {
        client_body_buffer_size 256k;
        client_max_body_size 32m;
        server_name filesender.domain.tld;
        index index.php;
        error_page 500 502 503 504 /50x.html;
        root /opt/filesender/www;
        location = /50x.html {
            root   /usr/share/nginx/html;
        }
        location / {
            try_files $uri $uri/ /index.php?args;
        }
        location ~ [^/]\.php(/|$) {
            fastcgi_split_path_info  ^(.+\.php)(/.+)$;
            fastcgi_pass  localhost:9090;
            include       fastcgi_params;
            fastcgi_intercept_errors on;
            fastcgi_param PATH_INFO       $fastcgi_path_info;
            fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        }
        location ^~ /saml {
            alias /opt/filesender/saml/www;
            location ~ ^(?<prefix>/saml)(?<phpfile>.+?\.php)(?<pathinfo>/.*)?$ {
                include fastcgi_params;
                fastcgi_pass  localhost:9090;
                fastcgi_param SCRIPT_FILENAME $document_root$phpfile;
                fastcgi_param PATH_INFO       $pathinfo if_not_empty;
            }
        }
        location ~* \.(ico|docx|doc|xls|xlsx|rar|zip|jpg|jpeg|txt|xml|pdf|gif|png|css|js)$ {
            root   /opt/filesender/www/;
        }
        location ~ /\. {
                deny all;
        }
}
```

And sure that your fastcgi_params file ( /etc/nginx/fastcgi_params ) looks like the following:

```
fastcgi_param   QUERY_STRING            $query_string;
fastcgi_param   REQUEST_METHOD          $request_method;
fastcgi_param   CONTENT_TYPE            $content_type;
fastcgi_param   CONTENT_LENGTH          $content_length;

fastcgi_param   SCRIPT_FILENAME         $request_filename;
fastcgi_param   SCRIPT_NAME             $fastcgi_script_name;
fastcgi_param   REQUEST_URI             $request_uri;
fastcgi_param   DOCUMENT_URI            $document_uri;
fastcgi_param   DOCUMENT_ROOT           $document_root;
fastcgi_param   SERVER_PROTOCOL         $server_protocol;

fastcgi_param   GATEWAY_INTERFACE       CGI/1.1;
fastcgi_param   SERVER_SOFTWARE         nginx/$nginx_version;

fastcgi_param   REMOTE_ADDR             $remote_addr;
fastcgi_param   REMOTE_PORT             $remote_port;
fastcgi_param   SERVER_ADDR             $server_addr;
fastcgi_param   SERVER_PORT             $server_port;
fastcgi_param   SERVER_NAME             $server_name;
fastcgi_param   HTTPS                   $https if_not_empty;
fastcgi_param   REDIRECT_STATUS         200;
```


And just set correct port ( for example port 9090 ) at file /etc/php5/fpm/pool.d/www.conf

```
...
listen = 127.0.0.1:9090
...
```


# Step 5 - Install and configure database

## Option a - PostgreSQL

On RedHat/CentOS, run:

	yum install -y php-pgsql

On Debian, run:

	apt-get install -y postgresql php5-pgsql

FileSender uses password based database logins and by default assumes that PostgreSQL is configured to accept password based sessions on 'localhost'. You should check and when needed change the relevant settings in the PostgreSQL pg_hba.conf configuration file. This file should have the following entries with **md5** listed as METHOD for local IPv4 and IPv6 connections:

	# Database administrative login by UNIX sockets local all postgres peer
	# TYPE DATABASE USER CIDR-ADDRESS METHOD
	# "local" is for Unix domain socket connections only local all all peer
	# IPv4 local connections: host all all 127.0.0.1/32 md5
	# IPv6 local connections: host all all ::1/128 md5

On Debian based systems this file will be in `/etc/postgresql/<version>/main/pg_hba.conf`. On Red Hat/Fedora based systems this file will be in `/var/lib/pgsql/data/pg_hba.conf`. When changing the pg_hba.conf file you'll have to restart the database server with (version number may be different or not needed depending on your system):

	service postgresql reload

Now create the database user `filesender` without special privileges and with a password. The command will prompt you to specify and confirm a password for the new database user. *This is the password you need to configure in the FileSender configuration file later on*.

	$ postgres createuser -S -D -R -P filesender
	Enter password for new role: <secret>
	Enter it again: <secret>

This will create a database user **filesender** without special privileges, and with a password. This password you will have to configure in the filesender config.php later on.

Create the filesender database with UTF8 encoding owned by the newly created filesender user:

	postgres createdb -E UTF8 -O filesender filesender

## Option b - MySQL

On RedHat/CentOS, run:

	yum install -y mariadb php-mysql mariadb-server
	systemctl --now enable mariadb
	mysql_secure_installation

On Debian, run:

	apt-get install -y mysql-server php5-mysql dpkg-reconfigure mysql-server

Create the filesender database:

	mysql -u root -p
	CREATE DATABASE `filesender` DEFAULT CHARACTER SET utf8;
	GRANT USAGE ON *.* TO 'filesender'@'localhost' IDENTIFIED BY '<your password>';
	GRANT CREATE, ALTER, SELECT, INSERT, INDEX, UPDATE, DELETE ON `filesender`.* TO 'filesender'@'localhost';
	FLUSH PRIVILEGES;
	exit

**Change from FileSender 1.x:** you now configure FileSender first and then use a FileSender script to initialise the database. See step 8 for initialising the database. Make sure you configure the correct database in the config file.

# Step 6 - Configure PHP5

## Automatic

A sample settings file is provided with FileSender in **config-templates/filesender-php.ini**. If you don't feel like manually editing your php.ini file, copy the filesender-php.ini file to your **/etc/php.d/** (RedHat/CentOS) or **/etc/php5/apache2/conf.d/** (Debian) directory to activate those settings.

On **RedHat/CentOS**, run:

    cp /opt/filesender/filesender/config-templates/filesender-php.ini /etc/php.d/
    systemctl reload httpd

On **Debian**, run:

	service apache2 reload

## Manual

To allow for max. 2 GB Flash uploads change these settings to the values indicated:

	max_input_time = 3600 ; in seconds
	upload_max_filesize = 2047M ; in M, the default value is 2MB
	post_max_size = 2146446312 ; in M, 2047M + 10K

* **NOTE**: when you edit your FileSender config.php remember to change `$config['max_flash_upload_size']` to match your `upload_max_filesize`. If they are not the same FileSender will use the lowest value as the actual maximum upload size for Flash uploads.

Ensure the php temporary upload directory points to a location with enough space:

	upload_tmp_dir = /tmp

* **NOTE**: You probably want to point this to the same directory you will use as your HTML5 upload temp directory (`$config['site_temp_filestore']`).
* **NOTE**: that this setting is for all PHP-apps, not only for filesender.

Turn on logging:

	log_errors = on error_log = syslog

Enable secure cookie handling to protect sessions:

	session.cookie_secure = On session.cookie_httponly = On

Reload your Apache server to activate the changes to your php.ini.

On **RedHat/CentOS**, run:

	systemctl reload httpd

On **Debian**, run:

	service apache2 reload

# Step 7 - Configure your FileSender installation

Copy the configuration template and edit it to match your site settings.

	cd /opt/filesender/filesender/config
	$EDITOR config.php

Be sure to at least set `$config['site_url']`, contact details, database settings and authentication configuration. The configuration file is self-explanatory.

# Step 8 - Initialise the FileSender database

Run:

	php /opt/filesender/filesender/scripts/upgrade/database.php

# Step 9 - Configure the FileSender clean-up cron job

	tee /etc/cron.daily/filesender <<EOF
	#!/bin/sh
	php -q /opt/filesender/filesender/scripts/task/cron.php
	EOF
	chmod +x /etc/cron.daily/filesender

# Step 10 - Optional local about, help, and landing pages

FileSender has provisions to allow you to have a local page for about,
help, and the landing (splash) page the user sees on your FileSender
site. While you could directly edit the page template for your language
doing that would not preserve your changes when you upgrade FileSender.

If you want a local about, help, or splash page create and edit a file
with the postfix ".local.php" and that local page will be served to
the user instead of the default. 

For example, the default help page for English language users might be from

/opt/filesender/language/en_AU/help_text.html.php

So you create a new page at the following location with your site
specific help text in it which would be served instead of the default.

/opt/filesender/language/en_AU/help_text.html.php.local.php

# Step 11 - Start using FileSender

Visit the URL to your FileSender instance.

	https://<your site>/filesender/

* **NOTE**: If you want your site to be available on `https://<your site>/`, without the /filesender, set `DocumentRoot /opt/filesender/filesender/www` in Apache and remember to update your `$config['site_url']` accordingly.

# Perfection

## SElinux

If you use RedHat/CentOS, you have SElinux installed. SElinux protects your system from unauthorised changes by attackers. FileSender supports SElinux, and if you followed this guide you have set up SElinux correctly with file storage in the same directory as FileSender.

If you want to store files on another location, set the context of this location to `httpd_sys_rw_content_t`, otherwise FileSender will fail trying to write there. If the other location is on an NFS share, be sure to set the following mount flag:

* `context=system_u:object_r:httpd_sys_rw_content_t:s0`

Example `/etc/fstab` line:

	nfs.filesender.org:/var/lib/filesender /var/lib/filesender nfs noexec,nolock,nfsvers=3,context=system_u:object_r:httpd_sys_rw_content_t:s0 0 0

### SEbooleans

#### httpd_can_sendmail

MUST be on for Apache to be able to send mail.

* `setsebool -P httpd_can_sendmail on`

#### httpd_use_nfs

MUST be off, use `context=system_u:object_r:httpd_sys_rw_content_t:s0` as a mount option instead if you use NFS.

* `setsebool -P httpd_use_nfs off`

#### httpd_can_network_connect_db

MAY be on, if you do not run the database on the local host.

* `setsebool -P httpd_can_network_connect_db on` (database is on another host)
* `setsebool -P httpd_can_network_connect_db off` (database is on localhost)

## HTTPS Only

Its good practice to disallow plain HTTP traffic and allow HTTPS only. Make a file in one of the following locations:

* **/etc/httpd/conf.d/000-forcehttps.conf** (RedHat/CentOS)
* **/etc/apache2/sites-available/000-forcehttps.conf** (Debian)

Add the following:

	<VirtualHost *:80>
		Redirect / https://filesender.example.org/
	</VirtualHost>

On **RedHat/CentOS**, run:

	service httpd reload

On **Debian**, run:

	a2ensite 000-forcehttps
	a2dissite 000-default
	service apache2 reload

## FileSender as main page

If you don't want your users to have to type `/filesender` after the hostname, you can add the following line to your filesender Apache configuration:

	RedirectMatch ^/(?!filesender/|simplesaml/)(.*) https://filesender.example.org/filesender/$1

# Support and Feedback

See [Support and Mailing lists](https://www.assembla.com/wiki/show/file_sender/Support_and_Mailinglists) and [Feature requests](https://www.assembla.com/wiki/show/file_sender/Feature_requests).
