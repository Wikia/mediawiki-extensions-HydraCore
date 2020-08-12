#!/bin/bash
while getopts ":h:u:p:d:" opt; do
  case $opt in
    h) host="$OPTARG"
    ;;
    u) user="$OPTARG"
    ;;
    p) pass="$OPTARG"
    ;;
    d) db="$OPTARG"
    ;;
    \?) echo "Invalid option -$OPTARG" >&2
    ;;
  esac
done
mysqldump -u$user -p$pass -h$host --ignore-table=$db.user --ignore-table=$db.user_global hydra
