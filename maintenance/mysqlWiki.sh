#!/bin/bash
SCRIPT_PATH=$(cd $(dirname $0); pwd -P)
COMMAND="$(${SCRIPT_PATH}/mysqlWiki.php $1)"
${COMMAND}