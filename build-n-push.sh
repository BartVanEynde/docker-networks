#!/bin/sh
#
# Bart Van Eynde
# build the image with the right tag & push it to gitea

docker build -t gitea.g4f.be/bart/docker-networks . && docker push gitea.g4f.be/bart/docker-networks 
