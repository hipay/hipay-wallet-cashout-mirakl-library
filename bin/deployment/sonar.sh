if [ -z "$CIRCLE_PULL_REQUEST" ];then
    echo "Not a pull request"
elif [ $CI_PULL_REQUESTS != "" ];then
    PR_NUMBER=$(echo $CIRCLE_PULL_REQUEST | cut -d'/' -f 7)
    sudo sonar-scanner -Dsonar.host.url=$SONAR_URL -Dsonar.login=$SONAR_LOGIN -Dsonar.analysis.mode=preview -Dsonar.github.pullRequest=$PR_NUMBER -Dsonar.github.oauth=$GITHUB_TOKEN
fi

if [ -z $CIRCLE_BRANCH ];then
    echo "Not a branch merge"
elif [ $CIRCLE_BRANCH = "develop" ];then
    sudo sonar-scanner -Dsonar.host.url=$SONAR_URL -Dsonar.login=$SONAR_LOGIN -Dsonar.sources=. -Dsonar.php.tests.reportPath=$CIRCLE_ARTIFACTS/logs.xml  -Dsonar.php.coverage.reportPaths=$CIRCLE_ARTIFACTS/coverage.xml
fi

