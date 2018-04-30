require 'yaml'

configuration = YAML::load(File.read("#{File.dirname(__FILE__)}/deploy/ansible/group_vars/development.yml"))

Vagrant.configure("2") do |config|
  config.vm.box = "bento/ubuntu-12.04"

  config.vm.synced_folder "simply-static", "/var/www/wordpress.local/wp-content/plugins/simply-static", owner: 'www-data', group: 'www-data'

  config.vm.provider :virtualbox do |v|
      # This setting makes it so that network access from inside the vagrant guest
      # is able to resolve DNS using the hosts VPN connection.
      v.customize ["modifyvm", :id, "--natdnshostresolver1", "on"]

      v.memory = 1024
      v.cpus = 1
  end

  config.vm.hostname = 'wordpress-development.local'
  config.hostsupdater.aliases = configuration['wordpress_sites'].collect{|s| s['host']}
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
