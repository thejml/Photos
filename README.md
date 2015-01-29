# Photos

This is attempting to be a simple yet extendable, deployable photo organization utility. It's aim is to deploy in a cloud based fashion (via EC2 or OpenShift or the like) and use either local storage or S3 buckets for photo storage. 

Thumbnail generation will happen upon request and/or save, and be cached locally for a set period of time or until a disk space threshold is reached. Originals will either be stored locally or in S3 depending on which class is chosen upon startup. 

File Data (Currently leveraging EXIF data read from the JPG file on save) will be stored in Elastic Search, along with user defined tagging information. This would allow searches based on photo parameters, tags, submitters, geo data where available, etc. 

Eventually links will be made 'sharable' and logins could be made to work with something like Google's OAUTH2 or similar, to provide granularity for image sharing and administration privileges.

There is currently a Trello board of basic tasks at: https://trello.com/b/q3PUN0t2/photo-organizer
