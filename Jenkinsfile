pipeline {
      environment {
    registry = "andreibaitov/wordpress"
    registryCredential = 'docker'

  }
      agent { label 'master'}
  stages {
      

     stage('Cloning git repositories') {
            parallel {
                stage('Clone docker repository') {
                    steps {
                        dir('docker') {
                            git url: 'https://github.com/AndreiBaitau/project_wordpress.git', branch: 'wordpress', credentialsId: 'git_cred'
                            sh 'ls -l'
                            
                           
                        }
                    }
                }
                stage('Clone wordpress repository') {
                    steps {
                        dir('wordpress') {
                            git url: 'https://github.com/AndreiBaitau/project_wordpress.git', branch: 'master'
                            sh 'ls -l'
                            
                           
                        }
                    }
                }
                 stage('Clone argo repository') {
                    steps {
                        dir('argo') {
                            git url: 'https://github.com/AndreiBaitau/argo-cd.git', branch:'project'
                            sh 'ls -l'
                          
                        }
                    }
                }
                
            }
            
        }

    stage ("Lint dockerfile") {
        agent {
            docker {
                image 'hadolint/hadolint:latest-debian'
                label 'master'
            }
        }
        steps {
            sh 'hadolint ./docker/Dockerfile | tee -a hadolint_lint.txt'
        }
        post {
            always {
                archiveArtifacts 'hadolint_lint.txt'
                            
            }

        }
    }
  stage('Building image') {
      steps{ 

        script {
             dockerImage = docker.build("$registry:1.$BUILD_NUMBER.1","./docker/ ")
        }
      }
    }

    stage('Push Image to repo') {
      steps{
        script {
            docker.withRegistry( '', registryCredential ) {
            dockerImage.push()
          }
        }
      }
    }
    stage('Changing files') {
            parallel {
    stage('Changing tag in helm chart') {
      steps{
        script {
          sh" sed -i 's/1.*/1.$BUILD_NUMBER.1/' ./wordpress/helm-source/wordpress/Chart.yaml"
          
            
        }
        }
      }
    stage('Changing tag in argo yaml') {
      steps{
        script {
          sh" sed -i 's/targetRevision: 1.*/targetRevision: 1.$BUILD_NUMBER.1/' ./argo/project_wordpress.yaml"
          
        }
        }}}
      }
      stage('Helm package') {
      steps{
        script {
          sh """
             cd wordpress/helm-source
             helm package wordpress
             mv wordpress-1.$BUILD_NUMBER.1.tgz ../helm-releases
             cd ..
             helm repo index --url https://andreibaitau.github.io/project_wordpress/ --merge index.yaml .
          """
          
        }
        }
      }
      
      stage('Push to wordpress repo') {
            steps {
                withCredentials([usernamePassword(credentialsId: 'git_cred', usernameVariable: 'USER', passwordVariable: 'PASS')]) {
                    script {
                        env.encodedPass=URLEncoder.encode(PASS, "UTF-8")
                    }
                    sh """
                        cd wordpress
                        git config --global user.email "baitovandrei@gmail.com"
                        git config --global user.name "AndreiBaitau"
                        git add --all
                        git commit -m "Change files No.$BUILD_NUMBER"
                        git push https://${USER}:${encodedPass}@github.com/AndreiBaitau/project_wordpress.git
                        sleep 5s
                    """
                }
            }
        }
      stage('Push to argo repo') {
            steps {
                withCredentials([usernamePassword(credentialsId: 'git_cred', usernameVariable: 'USER', passwordVariable: 'PASS')]) {
                    script {
                        env.encodedPass=URLEncoder.encode(PASS, "UTF-8")
                    }
                    sh """
                        cd argo
                        git config --global user.email "baitovandrei@gmail.com"
                        git config --global user.name "AndreiBaitau"
                        git add --all
                        git commit -m "Change files No.$BUILD_NUMBER"
                        git push https://${USER}:${encodedPass}@github.com/AndreiBaitau/argo-cd.git
                        sleep 5s
                    """
                }
            }
        }  
      
      
  }
    
    
  }
