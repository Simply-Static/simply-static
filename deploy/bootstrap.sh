#!/usr/bin/env bash

APPLICATION_ROOT=/vagrant
ANSIBLE_DIR=$APPLICATION_ROOT/deploy/ansible

PKG_OK=$(command -v ansible | grep "ansible")

if [ "" == "$PKG_OK" ]; then
    printf " "
    printf "############ INSTALLING ANSIBLE ############"
    # apt-get -y install ansible software-properties-common
    cd /tmp
    curl https://bootstrap.pypa.io/get-pip.py -o get-pip.py
    python get-pip.py
    pip install ansible
fi

# add vagrant user to www-data group
usermod -a -G www-data vagrant

cd $ANSIBLE_DIR
printf " "
printf "############ RUNNING ANSIBLE SCRIPTS ############"
ansible-playbook -c local -i development deploy-all.yml
