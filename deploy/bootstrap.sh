#!/usr/bin/env bash

APPLICATION_ROOT=/vagrant
ANSIBLE_DIR=$APPLICATION_ROOT/deploy/ansible

PKG_OK=$(dpkg-query -W --showformat='${Status}\n' ansible|grep "install ok installed")

if [ "" == "$PKG_OK" ]; then
    printf " "
    printf "############ INSTALLING ANSIBLE ############"
    apt-get -y install software-properties-common
    apt-add-repository -y ppa:ansible/ansible
    apt-get update
    apt-get -y install ansible
fi

cd $ANSIBLE_DIR
printf " "
printf "############ SETTING UP WORDPRESS DATABASE ############"
ansible-playbook -c local -i development dbservers.yml
printf " "
printf "############ SETTING UP WORDPRESS WEB SERVERS ############"
ansible-playbook -c local -i development webservers.yml
