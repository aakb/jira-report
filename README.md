# Jira report

## Create the key
See https://www.madboa.com/geek/openssl/#key-rsa
```
openssl genrsa -out mykey.pem 2048
openssl rsa -in mykey.pem -pubout
```

## Register application link in Jira
https://confluence.atlassian.com/adminjiraserver073/using-applinks-to-link-to-other-applications-861253079.html

### https://[SITE].atlassian.net/plugins/servlet/applinks/listApplicationLinks

"Create new link" -> Fill out "Incoming Authentication":
```
Consumer Key: [KEY]
Consumer Name: jira.vm
Public Key: Insert public key
Consumer Callback url: http://jira.vm/main/
```

Set values in .env:

```
JIRA_OAUTH_CUSTOMER_KEY=[KEY]
JIRA_OAUTH_PEM_PATH=[PATH TO PRIVATE KEY]
JIRA_URL='https://[SITE].atlassian.net'
JIRA_DEFAULT_BOARD=[TEAM BOARD ID]
```

## Links
https://docs.atlassian.com/jira-software/REST/7.3.1/
https://confluence.atlassian.com/adminjiraserver073/using-applinks-to-link-to-other-applications-861253079.html
https://bitbucket.org/atlassian_tutorial/atlassian-oauth-examples
