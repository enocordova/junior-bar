#!/bin/sh
# Gera certificado SSL autoassinado para desenvolvimento/teste
# Em produção, substituir por Let's Encrypt ou certificado real
openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
    -keyout /etc/nginx/ssl/server.key \
    -out /etc/nginx/ssl/server.crt \
    -subj "/C=PT/ST=Lisboa/L=Lisboa/O=JuniorBar/CN=localhost"
