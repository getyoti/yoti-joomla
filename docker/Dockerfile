FROM php:7.1-apache

ARG BRANCH

MAINTAINER Michael Babker <michael.babker@joomla.org> (@mbabker)

# Disable remote database security requirements.
ENV JOOMLA_INSTALLATION_DISABLE_LOCALHOST_CHECK=1

# Enable Apache Rewrite Module
RUN a2enmod rewrite

# Install PHP extensions
RUN apt-get update && apt-get install -y libpng12-dev libjpeg-dev libmcrypt-dev zip unzip && rm -rf /var/lib/apt/lists/* \
	&& docker-php-ext-configure gd --with-png-dir=/usr --with-jpeg-dir=/usr \
	&& docker-php-ext-install gd
RUN docker-php-ext-install mysqli
RUN docker-php-ext-install mcrypt
RUN docker-php-ext-install zip
RUN apt-get update && apt-get install -y zip unzip git vim nano

VOLUME /var/www/html

# Define Joomla version and expected SHA1 signature
ENV JOOMLA_VERSION 3.8.2
ENV DEFAULT_BRANCH master
ENV PLUGIN_PACKAGE_NAME yoti-joomla-v1.0.2-edge.zip
ENV JOOMLA_SHA1 e68745d55cc5b1a7bb46f72f4296c6550e088543

RUN if [ "$BRANCH" = "" ]; then \
  $BRANCH = $DEFAULT_BRANCH; \
fi

# Download package and extract to web volume
RUN curl -o joomla.zip -SL https://github.com/joomla/joomla-cms/releases/download/${JOOMLA_VERSION}/Joomla_${JOOMLA_VERSION}-Stable-Full_Package.zip \
	&& echo "$JOOMLA_SHA1 *joomla.zip" | sha1sum -c - \
	&& mkdir /usr/src/joomla \
	&& unzip joomla.zip -d /usr/src/joomla \
	&& rm joomla.zip \
	&& chown -R www-data:www-data /usr/src/joomla

RUN git clone -b ${BRANCH} https://github.com/getyoti/yoti-joomla.git --single-branch /usr/src/yoti-joomla \
    	&& echo "Finished cloning ${BRANCH}" \
	&& chown -R www-data:www-data /usr/src/yoti-joomla \
	&& cd /usr/src/yoti-joomla \
	&& mkdir __sdk-sym \
	&& ./pack-plugin.sh \
	&& mv ./${PLUGIN_PACKAGE_NAME} /usr/src/joomla \
	&& cd /usr/src/joomla \
	&& mkdir yoti && mv ./${PLUGIN_PACKAGE_NAME} yoti \
	&& cd yoti && unzip ${PLUGIN_PACKAGE_NAME} \
	&& cd .. \
	&& mv yoti/com_yoti.xml yoti/admin/ \
	&& mv yoti/process-script.php yoti/admin/ \
	&& mv yoti/admin yoti/com_yoti \
	&& mv yoti/com_yoti administrator/components \
	&& mv yoti/site yoti/com_yoti \
	&& mv yoti/com_yoti components \
	&& mv yoti/modules/mod_yoti modules \
	&& mv yoti/plugins/yotiprofile plugins/user \
	&& echo "Yoti extension installled" 

RUN echo "Yoti Branch ${BRANCH}"

# Copy init scripts and custom .htaccess
COPY docker-entrypoint.sh /entrypoint.sh
COPY makedb.php /makedb.php

RUN chmod +x /entrypoint.sh

ENTRYPOINT ["/entrypoint.sh"]
CMD ["apache2-foreground"] 
