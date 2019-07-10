FROM docker_joomla-base:latest

ARG BRANCH

ENV DEFAULT_BRANCH master
ENV PLUGIN_PACKAGE_NAME yoti-joomla-extension-edge.zip

RUN if [ "$BRANCH" = "" ]; then \
  $BRANCH = $DEFAULT_BRANCH; \
fi

RUN git clone -b ${BRANCH} https://github.com/getyoti/yoti-joomla.git --single-branch /usr/src/yoti \
    && echo "Finished cloning ${BRANCH}" \
	&& chown -R www-data:www-data /usr/src/yoti \
	&& cd /usr/src/yoti \
	&& ./pack-plugin.sh \
	&& mv ./${PLUGIN_PACKAGE_NAME} /usr/src/joomla \
	&& cd /usr/src/joomla \
	&& mkdir yoti && mv ./${PLUGIN_PACKAGE_NAME} yoti \
	&& cd yoti && unzip ${PLUGIN_PACKAGE_NAME} \
	&& cd .. \
	&& mv yoti/com_yoti.xml yoti/admin/ \
	&& mv yoti/process-script.php yoti/admin/ \
	&& mv yoti/admin administrator/components/com_yoti \
	&& mv yoti/site components/com_yoti \
	&& mv yoti/modules/mod_yoti modules \
	&& mv yoti/plugins/yotiprofile plugins/user \
	&& echo "Yoti extension installed"

RUN echo "Yoti Branch ${BRANCH}"
