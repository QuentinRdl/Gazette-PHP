FROM alpine AS apache-php8.3-install

LABEL version="1.0" maintainer="Eric Merlet"

RUN apk update \
&& apk add apache2 \
&& apk add php83 php83-mysqli phpmyadmin php83-apache2 php83-pdo php83-pdo_mysql \
&& apk add php83-bz2 php83-ctype php83-curl php83-gd php83-iconv php83-mbstring php83-session php83-xmlwriter php83-zip \
&& apk add php83-bcmath php83-fileinfo php83-intl php83-json php83-xml icu-data-full \
&& rm -rf /var/cache/apk/*

################################################################################################

FROM apache-php8.3-install AS apache-php8.3-config

ARG DOCUMENTROOT="/var/www/localhost/htdocs"

RUN rm ${DOCUMENTROOT}/index.html \
&& mkdir -p /var/lib/phpmyadmin/tmp \
&& chown -R apache:apache /var/lib/phpmyadmin/ /etc/phpmyadmin/ \
&& sed -i -E 's|^#(ServerName).*$|\1 127\.0\.0\.1:80|' /etc/apache2/httpd.conf \
&& sed -i -E 's|^(display_errors)[[:space:]]*=[[:space:]]*Off$|\1 = On|' /etc/php83/php.ini \
&& sed -i -E 's|^(display_startup_errors)[[:space:]]*=[[:space:]]*Off$|\1 = On|' /etc/php83/php.ini \
&& sed -i -E "s|^(\\\$cfg\\['blowfish_secret'\\])(.*)\$|// \\1\\2\\n\\1 = sodium_hex2bin('f16ce59f45714194371b48fe362072dc3b019da7861558cd4ad29e4d6fb13851');|" /etc/phpmyadmin/config.inc.php \
&& sed -i -E "s|^(\\\$cfg\\['Servers'\\]\\[\\\$i\\]\\['host'\\]).*\$|\\1 = 'mariadb\\-hostname';|" /etc/phpmyadmin/config.inc.php \
&& sed -i -E "s|^//[[:space:]]*(\\\$cfg\\['Servers'\\]\\[\\\$i\\]\\['controluser'\\]).*\$|\\1 = 'pma';|" /etc/phpmyadmin/config.inc.php \
&& sed -i -E "s|^//[[:space:]]*(\\\$cfg\\['Servers'\\]\\[\\\$i\\]\\['controlpass'\\]).*\$|\\1 = 'pma_pass';|" /etc/phpmyadmin/config.inc.php \
&& echo "\$cfg['TempDir'] = '/var/lib/phpmyadmin/tmp';" >> /etc/phpmyadmin/config.inc.php \
&& echo '// This search is case-sensitive and will match the exact string only. If your setup does not use SSL but is safe because you are using a local connection or private network, you can add your hostname or IP to the list. You can also remove the default entries to only include yours.' >> /etc/phpmyadmin/config.inc.php \
&& echo "\$cfg['MysqlSslWarningSafeHosts'] = ['127.0.0.1', 'localhost', 'mariadb-hostname'];" >> /etc/phpmyadmin/config.inc.php


# Inform the outside world that this port is opened
EXPOSE 80

# RÉPERTOIRE DE TRAVAIL
WORKDIR  ${DOCUMENTROOT}

ENTRYPOINT  /usr/sbin/httpd -f /etc/apache2/httpd.conf -DFOREGROUND

# docker build --tag=alpine-apache-php8.3 --target=apache-php8.3-config .

################################################################################################
################################################################################################

FROM alpine AS mariadb-install

LABEL version="1.0" maintainer="Eric Merlet"

RUN apk update \
&& apk add mariadb mariadb-client \
&& rm -rf /var/cache/apk/*

################################################################################################

FROM mariadb-install AS mariadb-config

ARG ROOTPASS="root_pass"

COPY --from=apache-php8.3-install  /usr/share/webapps/phpmyadmin/sql/create_tables.sql /tmp/create_tables.sql

RUN sed -i -E 's|^(skip\-networking)$|\1 = 0\nport = 3306|' /etc/my.cnf.d/mariadb-server.cnf \
&& mysql_install_db --user=mysql --datadir=/var/lib/mysql \
&& echo '#! /bin/sh' > /usr/local/bin/mariadb_start_background.sh \
&& echo 'cd /usr' >> /usr/local/bin/mariadb_start_background.sh \
&& echo '/usr/bin/mariadbd-safe --datadir=/var/lib/mysql &' >> /usr/local/bin/mariadb_start_background.sh \
&& echo 'while ! test -S /run/mysqld/mysqld.sock; do sleep 1; done' >> /usr/local/bin/mariadb_start_background.sh \
&& chmod +x /usr/local/bin/mariadb_start_background.sh \
&& /usr/local/bin/mariadb_start_background.sh \
&& echo > /tmp/mysql_secure_install_input \
&& for I in n Y ${ROOTPASS} ${ROOTPASS} Y n Y Y; do echo $I >> /tmp/mysql_secure_install_input; done \
&& mysql_secure_installation < /tmp/mysql_secure_install_input \
&& mariadb -uroot -p${ROOTPASS} < /tmp/create_tables.sql \
&& echo "GRANT ALL PRIVILEGES ON *.* TO 'root'@'%' IDENTIFIED BY '${ROOTPASS}' WITH GRANT OPTION;" > /tmp/script.sql \
&& echo "CREATE USER IF NOT EXISTS 'pma'@'%' IDENTIFIED BY 'pma_pass';" >> /tmp/script.sql \
&& echo 'GRANT ALL PRIVILEGES ON `phpmyadmin`.* TO "pma"@"%" WITH GRANT OPTION;' >> /tmp/script.sql \
&& echo 'USE mysql;' >> /tmp/script.sql \
&& echo 'DELETE FROM user WHERE user="PUBLIC";' >> /tmp/script.sql \
&& mariadb -uroot -p${ROOTPASS} < /tmp/script.sql \
&& rm /tmp/mysql_secure_install_input /tmp/create_tables.sql /usr/local/bin/mariadb_start_background.sh /tmp/script.sql

# Inform the outside world that this port is opened
EXPOSE 3306

ENTRYPOINT cd /usr && /usr/bin/mariadbd-safe --datadir=/var/lib/mysql

# docker build --tag=alpine-mariadb --target=mariadb-config .
