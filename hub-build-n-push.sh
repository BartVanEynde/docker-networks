#!/bin/sh
#
# Bart Van Eynde
# build the image with the right tag & push it to gitea

docker build -t bartvaneynde/docker-networks . && docker push bartvaneynde/docker-networks
