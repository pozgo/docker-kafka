FROM polinux/php:7.1

RUN \
  rpm --rebuilddb && yum clean all && \
  yum install -y php71-php-pecl-rdkafka && \
  yum clean all
  
