load 'deploy'

#the domain name for the server you'll be running wordpress on
set :domain, "localhost"

#other domain names your app will respond to (dev.blog.com, etc)
#set :server_aliases, []

#the name of this wordpress project
set :application, "cj-wordpress-capistrano-test"

#your repo
set :repository,  "git@github.com:timnovinger/cj-wordpress-capistrano.git"

require File.join(File.dirname(__FILE__), 'lib', 'deploy', 'wordpress')

#Customizations
#==============

# Unless set here, we prompt you for these three on `cap setup:wordpress`
# set :wordpress_db_host, "localhost"
# set :wordpress_db_name, ""
# set :wordpress_db_user, ""
# set :wordpress_db_password, ""

# Wordpress path. Unless set here, these variables *WILL NOT* be set in wp-config.php
# set :wordpress_home, "http://www.yourdomain.com"
# set :wordpress_siteurl, "http://www.yourdomain.com"

#these are randomized on `cap setup:wordpress`
# set :wordpress_auth_key, Digest::SHA1.hexdigest(rand.to_s)
# set :wordpress_secure_auth_key, Digest::SHA1.hexdigest(rand.to_s)
# set :wordpress_logged_in_key, Digest::SHA1.hexdigest(rand.to_s)
# set :wordpress_nonce_key, Digest::SHA1.hexdigest(rand.to_s)
