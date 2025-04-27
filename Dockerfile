FROM alpine

LABEL maintainer="Bart Van Eynde <docker@bartvaneynde.be>"
LABEL description "An insight in docker networking by visualizing the json info in html tables"
LABEL github_url="https://gitea.g4f.be/bart/docker-networks"
LABEL org.opencontainers.image.source https://gitea.g4f.be/bart/docker-networks
LABEL org.opencontainers.image.description "An insight in docker networking by visualizing the json info in html tables"

RUN apk add --update --no-cache \
	lighttpd \
    php84-cgi \
    php84-curl \
    fcgi \
    curl \
    && rm -rf /var/cache/apk/*

COPY etc/lighttpd/* /etc/lighttpd/
COPY start.sh /usr/local/bin/
COPY index.php /var/www/localhost/htdocs/

EXPOSE 80

VOLUME /var/www/localhost/htdocs
VOLUME /etc/lighttpd

RUN mkdir -p /run/lighttpd && chown lighttpd:lighttpd /run/lighttpd 

HEALTHCHECK --interval=1m --timeout=1s \
  CMD curl -f http://localhost/ || exit 1

CMD ["start.sh"]
