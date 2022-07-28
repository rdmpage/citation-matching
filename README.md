# Citation matching

Tools to match short citations to cited works, such as those that occur in taxonomic databases and publications (“microcitations”). Typically these citations are to one or more pages in a work, not the complete work itself. For work on parsing full citations of works (such as appear in the reference list at the end of a paper) see, for example [rdmpage/citation-parsing](https://github.com/rdmpage/citation-parsing).

The goals of this work are:
- parse short citations into structured data
- match short citations to the complete work (e.g., a paper with a DOI)
- match short citations to their digital equivalent (e.g., the page in the Biodiversity Heritage Library).

The focus will be on a set of APIs that can be used to process data, rather than a web interface.

## Text matching

[Microcitations: linking nomenclators to BHL](https://iphylo.blogspot.com/2011/03/microcitations-linking-nomenclators-to.html) discussed validating matches between microcitations and BHL by using associated taxonomic names. We will need to use approximate matching to accommodate OCR errors. The `api_text.php` service takes a short string (the “needle”) and attempts to find it in a larger body of text (the “haystack”) using approximate search [elonen/php-approximate-search](https://github.com/elonen/php-approximate-search).
 
