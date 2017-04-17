Vagrant.configure("2") do |config|
  config.vm.box = 'ubuntu/trusty64' # 14.04

  config.vm.synced_folder ".", "/vagrant"

  config.vm.synced_folder ".", "/var/www/wordpress/wp-content/plugins/simply-static", owner: 'www-data', group: 'www-data'

  config.vm.provider :virtualbox do |v|
      # This setting makes it so that network access from inside the vagrant guest
      # is able to resolve DNS using the hosts VPN connection.
      v.customize ["modifyvm", :id, "--natdnshostresolver1", "on"]

      v.memory = 1024
      v.cpus = 1
  end

  config.vm.hostname = 'wordpress.dev'
  config.hostsupdater.remove_on_suspend = true

  # Assign this VM to a host-only network IP, allowing you to access it
  # via the IP. Host-only networks can talk to the host machine as well as
  # any other machines on the same network, but cannot be accessed (through this
  # network interface) by any external networks.
  config.vm.network "private_network", ip: "192.168.3.49"

  # Bootstrap using ansible
  config.vm.provision :shell do |s|
      s.path = 'deploy/bootstrap.sh'
  end

end
