```
__________.__          ___________.__  .__       .__        
\______   \  |__ ______\_   _____/|  | |__|__  __|__|______ 
 |     ___/  |  \\____ \|    __)_ |  | |  \  \/  /  \_  __ \
 |    |   |   Y  \  |_> >        \|  |_|  |>    <|  ||  | \/
 |____|   |___|  /   __/_______  /|____/__/__/\_ \__||__|   
               \/|__|          \/               \/          
                                                 Copy Module
```

Provides the ability to copy files from one location to another.

### Copy

Copies files from a file path or a folder path to a specified folder or file name.

Folder paths can be configured to get the top level directory using '/*' or for all files and folders in path by using '/**'.

Further configuration can be added using the standard query string format.

* filter - comma deliminated list of extensions.

```yaml
copy:
    PATH_BOWER + /jquery/dist/jquery.min.js: PATH_PUBLIC_ASSETS + /vendor/jquery/
    PATH_RES_ASSET_IMAGES + /**?filter=png: PATH_PUBLIC_ASSETS + /images/
```