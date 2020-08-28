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

rm /tmp/$db.sql*
mysqldump -u$user -p$pass -h$host --ignore-table=$db.user --ignore-table=$db.user_global --ignore-table=$db.actor $db > /tmp/$db.sql
mysqldump -u$user -p$pass -h$host hydra actor >> /tmp/$db.sql
bzip2 -k /tmp/$db.sql
