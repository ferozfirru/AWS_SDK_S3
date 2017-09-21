# AWS_SDK_S3
An S3 operations Class for AWS PHP SDK by Fenix

### Create class object

```PHP
    $ss = new S3API;
```
### function calls
```PHP
    $ss->listbuckets(); // list all Buckets
    $ss->listfiles('bucketname'); // list all files in given s3 bucket
    $ss->copy(__DIR__."/localdir/fertest.xls","/fol2/copied.xls",'bucketname'); // copy local file to given S3 bucket
    $ss->geturl('s3fol1/test.xls','bucketname'); // get file downloadble url for given s3 file name
    $ss->copyremote('fol1/test.txt','fol2/test.txt','bucket1','bucket2'); // copy remote s3 files from one bucket to another
    $ss->savetolocal("s3dir/fertest.xls",__DIR__."/downfile.xls","bucketname"); // Save s3 bucket file in given local path
    $ss->download('/fol1/test.xls','bucketname','D'); // download give file from bucket, 'D' for header download,'I' to show inline in browser
    $ss->delete('/fol1/test.xls','bucketname'); // remove give file from given bucket
    $ss->deletemultiple(['/fol1/f1.txt','/fol2/f2.txt',...],'bucketname'); //delete multiple files from given bucket
    $ss->getlocallist('/localdir/'); // get all files in given local path
    $ss->error; // returns FALSE on error
```
