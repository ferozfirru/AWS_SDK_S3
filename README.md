# AWS_SDK_S3
An S3 operations Class for AWS PHP SDK by Fenix

#Create class object

```PHP
    $ss = new S3API;
```
###function calls
```PHP
    $ss->listbuckets(); 
    $ss->listfiles('bucketname');
    $ss->copy(__DIR__."/localdir/fertest.xls","/fol2/copied.xls",'bucketname');
    $ss->geturl('fol1/test.xls','bucketname');
    $ss->copyremote('fol1/test.txt','fol2/test.txt','bucket1','bucket2');
    $ss->savetolocal("localdir/fertest.xls",__DIR__."/downfile.xls","bucketname");
    $ss->download('/fol1/test.xls','bucketname','D');
    $ss->delete('/fol1/test.xls','bucketname');
    $ss->deletemultiple([['key'=>'/fol1/f1.txt'],['key'=>'/fol2/f2.txt']],'bucketname');
    
    $ss->error; // returns FALSE on error
```
