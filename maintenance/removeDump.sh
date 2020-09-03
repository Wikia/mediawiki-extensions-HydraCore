#!/bin/bash
while getopts ":d:" opt; do
  case $opt in
    d) db="$OPTARG"
    ;;
    \?) echo "Invalid option -$OPTARG" >&2
    ;;
  esac
done

rm /tmp/$db.sql*
