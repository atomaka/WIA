# Load the rails application
require File.expand_path('../application', __FILE__)

# Initialize the rails application
WwwWhoisandrewCom::Application.initialize!

# Include the URL validator
require 'url_validator'