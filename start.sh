#!/bin/bash

export PROJECT_NAME=${PWD##*/}

docker compose config

docker compose build app

docker compose up -d

