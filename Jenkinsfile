#!groovy

stage('Prepare build') {
    userInput = input(message: 'Launch acceptance tests?', parameters: [
        [
            $class: 'ChoiceParameterDefinition',
            name: 'storage',
            choices: 'odm\norm',
            description: 'Storage used for the build, MongoDB (default) or MySQL'
        ],
        [
            $class: 'TextParameterDefinition',
            name: 'features',
            defaultValue: 'features',
            description: 'Behat scenarios to build'
        ]
    ])

    node {
        deleteDir()
        checkout scm

        sh "mkdir -p app/build/logs/behat"
        sh "mkdir -p app/build/logs/consumer"
        sh "mkdir -p app/build/screenshots"

        // Set composer.json
        sh "composer require --dev --no-update \"friendsofphp/php-cs-fixer\":\"1.12.*\""

        // Configure the PIM
        sh "cp app/config/parameters.yml.dist app/config/parameters_test.yml"
        sh "sed -i \"s#database_host: .*#database_host: mysql_pim_community_dev_${env.BUILD_NUMBER}#g\" app/config/parameters_test.yml"
        sh "printf \"    installer_data: 'PimInstallerBundle:minimal'\n\" >> app/config/parameters_test.yml"

        // Activate MongoDB if needed
        if ('odm' == userInput['storage']) {
            sh "sed -i \"s@// new Doctrine@new Doctrine@g\" app/AppKernel.php"
            sh "sed -i \"s@# mongodb_database: .*@mongodb_database: akeneo_pim@g\" app/config/pim_parameters.yml"
            sh "sed -i \"s@# mongodb_server: .*@mongodb_server: 'mongodb://mongodb:27017'@g\" app/config/pim_parameters.yml"
            sh "printf \"    pim_catalog_product_storage_driver: doctrine/mongodb-odm\n\" >> app/config/parameters_test.yml"
        }

        // Install needed dependencies
        sh "composer update --optimize-autoloader --no-interaction --no-progress --prefer-dist --ignore-platform-reqs"
        sh "app/console oro:localization:dump"
        sh "app/console oro:requirejs:generate-config"
        sh "npm install"

        stash "project_files"
    }
}

parallel 'behat': {
    stage('Acceptance tests: behat') {
        node {
            unstash "project_files"

            sh "/usr/bin/php7.0 /var/lib/distributed-ci/dci-master/bin/build -p 5.6 -m 5.5 ${env.WORKSPACE} ${env.BUILD_NUMBER} pim-community-dev ${userInput['storage']} ${userInput['features']} akeneo/job/pim-community-dev/view/Pull%20Requests/job/${env.JOB_BASE_NAME} 3"

            step([$class: 'ArtifactArchiver', allowEmptyArchive: true, artifacts: 'app/build/screenshots/*.png,app/build/logs/consumer/*.log', defaultExcludes: false, excludes: null])
            step([$class: 'JUnitResultArchiver', testResults: 'app/build/logs/behat/*.xml'])
        }
    }
},
// for (platform in [ '5.4', '5.5', '5.6', '7.0' ]) {
'phpunit': {
    stage('Unit tests: phpunit') {
        node {
            unstash "project_files"

            sh "bin/phpunit -c app/phpunit.travis.xml --testsuite PIM_Unit_Test"
        }
    }
},
'phpspec': {
    stage('Unit tests: phpspec') {
        node {
            unstash "project_files"

            sh "bin/phpspec run --format=dot"
        }
    }
},
'jasmine': {
    stage('Unit tests: jasmine') {
        node {
            unstash "project_files"

            sh "grunt test --force"
        }
    }
},
'php-cs-fixer': {
    stage('Code style: php-cs-fixer') {
        node {
            unstash "project_files"

            sh "bin/php-cs-fixer fix --dry-run -v --diff --config-file=.php_cs.php"
        }
    }
},
'grunt': {
    stage('Code style: grunt') {
        node {
            unstash "project_files"

            sh "grunt codestyle --force"
        }
    }
}
// }
