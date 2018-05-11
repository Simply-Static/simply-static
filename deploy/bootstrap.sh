#!/usr/bin/env bash

APPLICATION_ROOT=/vagrant
ANSIBLE_DIR=$APPLICATION_ROOT/deploy/ansible

PKG_OK=$(dpkg-query -W --showformat='${Status}\n' ansible|grep "install ok installed")

if [ "" == "$PKG_OK" ]; then
    printf " "
    printf "############ INSTALLING ANSIBLE ############"
    # apt-get -y install ansible software-properties-common
    cd /tmp
    curl https://bootstrap.pypa.io/get-pip.py -o get-pip.py
    python get-pip.py
    pip install ansible
fi

cd $ANSIBLE_DIR
printf " "
printf "############ SETTING UP WORDPRESS WEB SERVER ############"
ansible-playbook -c local -i development deploy-all.yml
