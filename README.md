# thumber

__Creates image thumbnails from PDF files.__

Thumber generates a thumbnail for your PDFs. You can call it using a single tag in your template.

### Requirements:
 - This plugin requires [ImageMagick](http://www.imagemagick.org/) and [Ghostscript](http://www.ghostscript.com/) to be installed
 - You should create a directory for your cached thumbnails to live. The default directory is specified as `/images/thumber`. Thumber should have permissions to write to this directory

### Example usage:

```
{exp:thumber:create src="/uploads/documents/yourfile.pdf" page='1' extension='jpg' height='250' class='awesome' title='Click to download' link='yes'}
```

### Parameters:
 - `src`: The source PDF ___[Required]___
 - `width`: The width of the generated thumbnail
 - `height`: The height of the generated thumbnail
 - `page`: The page of the PDF used to generate the thumbnail ___[Default: 1]___
 - `extension`: The file type of the generated thumbnail ___[Default: png]___
 - `link`: Wrap the thumbnail in a link to the PDF ___[Default: no]___

Any other parameters will be passed directly to the generated html snippet - so if you want to add an `id` or `class`, just add them as parameters.

### Todos:
 - We plan to add a `crop` parameter, to determine whether the thumbnail should be cropped