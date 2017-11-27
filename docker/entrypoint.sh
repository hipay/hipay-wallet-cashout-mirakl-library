#!/bin/sh -e

COLOR_SUCCESS='\033[0;32m'
NC='\033[0m'

exec apache2-foreground
