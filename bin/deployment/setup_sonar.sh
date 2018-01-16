sudo apt-get install unzip
sudo wget https://sonarsource.bintray.com/Distribution/sonar-scanner-cli/sonar-scanner-cli-3.0.3.778-linux.zip
sudo mv sonar-scanner-cli-3.0.3.778-linux.zip ~/
sudo unzip ~/sonar-scanner-cli-3.0.3.778-linux.zip -d ~/
sudo chmod -R a+x ~/sonar-scanner-3.0.3.778-linux/
sudo ln -s --force ~/sonar-scanner-3.0.3.778-linux/bin/sonar-scanner /usr/local/bin/sonar-scanner
