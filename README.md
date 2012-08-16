# thumber

__Creates image thumbnails from PDF files.__

Thumber generates a thumbnail for your PDFs. You can call it using a single tag in your template.

### Requirements:
 - This plugin requires imagemagick and ghostscript to be installed.
 - You should create a directory for your cached thumbnails to live. The default directory is specified as `/images/thumber`. Thumber should have permissions to write to this directory.

### Example usage:

```
{exp:thumber:create src="/uploads/documents/yourfile.pdf" page='1' extension='jpg' height='250' class='awesome' title='Click to download' link='yes'}
```

### Parameters:
 - `src`: The source PDF. This parameter is required.
 - `width`: The width of the generated thumbnail.
 - `height`: The height of the generated thumbnail.
 - `page`: The page of the PDF used to generate the thumbnail. _[Default: __1__]_
 - `extension`: The file type of the generated thumbnail. _[Default: __png__]_
 - `link`: Wrap the thumbnail in a link to the PDF. _[Default: __no__]_

Any other parameters will be passed directly to the generated html snippet - so if you want to add an `id` or `class`, just add them as parameters.

### Todos:
 - We plan to add a `crop` parameter, to determine whether the thumbnail should be cropped.