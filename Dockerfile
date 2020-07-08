FROM centos:8
MAINTAINER Sean Cline <smcline06@gmail.com>

## --- SUPPORTING FILES ---
COPY cacti /cacti_install

## --- UPDATE OS, INSTALL EPEL ---
RUN \
<<<<<<< HEAD
    rpm --rebuilddb && yum clean all && \
    yum install -y \
        rrdtool net-snmp net-snmp-utils cronie php-ldap php-devel mysql php \
        ntp bison php-cli php-mysql php-common php-mbstring php-snmp curl \
        php-gd openssl openldap mod_ssl php-pear net-snmp-libs php-pdo \
        autoconf automake gcc gzip help2man libtool make net-snmp-devel \
        m4 libmysqlclient-devel libmysqlclient openssl-devel dos2unix wget \
        sendmail mariadb-devel which epel-release && \
    yum upgrade -y && \
    yum install -y fping && \
=======
    yum update -y && \
    yum install -y https://dl.fedoraproject.org/pub/epel/epel-release-latest-8.noarch.rpm && \
    yum install -y dnf-plugins-core && \
    yum config-manager --set-enabled PowerTools && \
    yum clean all

## --- PHP EXTENTIONS ---
RUN \
    yum install -y \
        php php-xml php-session php-sockets php-ldap php-gd \
        php-json php-mysqlnd php-gmp php-mbstring php-posix \
        php-snmp php-intl php-common php-cli php-devel php-pear \
        php-pdo && \
>>>>>>> e347c2052517fcba388b580bce1ba5820ef38feb
    yum clean all

## --- CACTI/SPINE Requirements ---
RUN \
    yum install -y \
        rrdtool net-snmp net-snmp-utils cronie mariadb autoconf \
        bison openssl openldap mod_ssl net-snmp-libs automake \
        gcc gzip libtool make net-snmp-devel dos2unix m4 which \
        openssl-devel mariadb-devel sendmail curl wget help2man && \
    yum clean all

## --- SERVICE CONFIGS ---
COPY configs /template_configs
COPY configs/crontab /etc/crontab

## --- SETTINGS/EXTRAS ---
COPY plugins /cacti_install/plugins
COPY templates /templates
COPY settings /settings

## --- SCRIPTS ---
COPY upgrade.sh /upgrade.sh
COPY restore.sh /restore.sh
COPY backup.sh /backup.sh
<<<<<<< HEAD
RUN chmod +x /backup.sh
COPY createdevices.sh /createdevices.sh
RUN chmod +x createdevices.sh
RUN mkdir /backups
RUN mkdir /cacti
RUN mkdir /spine
=======

RUN  \
    chmod +x /upgrade.sh && \
    chmod +x /restore.sh && \
    chmod +x /backup.sh && \
    mkdir /backups && \
    mkdir /cacti && \
    mkdir /spine
>>>>>>> e347c2052517fcba388b580bce1ba5820ef38feb

## -- MISC SETUP --
RUN echo "ServerName localhost" > /etc/httpd/conf.d/fqdn.conf
RUN /usr/libexec/httpd-ssl-gencerts

## --- ENV ---
ENV \
    DB_NAME=cacti \
    DB_USER=cactiuser \
    DB_PASS=cactipassword \
    DB_HOST=localhost \
    DB_PORT=3306 \
    RDB_NAME=cacti \
    RDB_USER=cactiuser \
    RDB_PASS=cactipassword \
    RDB_HOST=localhost \
    RDB_PORT=3306 \
    BACKUP_RETENTION=7 \
    BACKUP_TIME=0 \
    REMOTE_POLLER=0 \
    INITIALIZE_DB=0 \
    TZ=UTC \
    PHP_MEMORY_LIMIT=800M \
    PHP_MAX_EXECUTION_TIME=60 \
    PHP_SNMP=1

## --- Start ---
COPY start.sh /start.sh
CMD ["/start.sh"]

EXPOSE 80 443
