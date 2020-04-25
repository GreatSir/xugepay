FROM registry.cn-shenzhen.aliyuncs.com/loopyun/loopyun-xinchao-php7:1.0
ADD ./ /data/wwwroot/
ADD ./nginx.conf /etc/nginx
ADD ./php-fpm.conf /usr/local/etc
RUN mkdir -p /usr/share/nginx/logs
RUN touch /usr/share/nginx/logs/error.log
ENV ACTION test
WORKDIR /data/wwwroot
ENTRYPOINT chown -R www.www /data/wwwroot /usr/share/nginx/logs && service cron start && php-fpm && nginx -g 'daemon off;'