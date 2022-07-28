# Citation matching

Tools to match short citations to cited works, such as those that occur in taxonomic databases and publications (“microcitations”). Typically these citations are to one or more pages in a work, not the complete work itself. For work on parsing full citations of works (such as appear in the reference list at the end of a paper) see, for example [rdmpage/citation-parsing](https://github.com/rdmpage/citation-parsing).

The goals of this work are:
- parse short citations into structured data
- match short citations to the complete work (e.g., a paper with a DOI)
- match short citations to their digital equivalent (e.g., the page in the Biodiversity Heritage Library).

The focus will be on a set of APIs that can be used to process data, rather than a web interface.

## Notes

### Parsing

The parser uses regular expressions. The output includes XML-style tagging so that if we move to approaches such as CRF we can easily generate training data.

### Matching containers

Microcitations often have abbreviated journal names. Ideally these “strings” will be matched to “things” with identifiers, such as ISSNs.  

### ISSNs

The [International Standard Serial Number](https://en.wikipedia.org/wiki/International_Standard_Serial_Number) (ISSN) is a standard identifier for journals. Based on previous projects I have assembled a set of journal names and abbreviations and the corresponding ISSNs. These can be found as CSV files in the `database/issn` directory. The file `import-issn.php` will import these into the database `matching.db`.

The file `extra.tsv` contains a list of titles that can be added to as we discover journals and/or abbreviations that are missed.

### BHL

BHL has a data dump matching external identifiers such as ISSNs to BHL’s own title identifiers (`titleidentifier.txt`). The file `import-bhl.php` will import these into the database `matching.db`.

If an article potential exists in BHL we can use the BHL OpenURL resolver to locate the corresponding page (or pages). Note that there may be more than one matching page if BHL has multiple scans of the same volume, or a scan comprising multiple volumes.


### Journal abbreviations

[ISO 4](https://en.wikipedia.org/wiki/ISO_4) defines rules for abbreviating titles. There is a file of abbreviations available from ISSN.org: https://www.issn.org/wp-content/uploads/2021/07/ltwa_20210702.csv. The file `import-ltwa.php` will import this CSV file into a SQLite database called `matching.db`;




### Text matching

[Microcitations: linking nomenclators to BHL](https://iphylo.blogspot.com/2011/03/microcitations-linking-nomenclators-to.html) discussed validating matches between microcitations and BHL by using associated taxonomic names. If we have a taxonomic name and a citation, one way to valid that we’ve matched the citation to the correct page in BHL is to see whether that page mentions the taxonomic name. The presence of OCR errors means we will need to use approximate matching to check for the presence of the taxon name.

The `api_text.php` service takes a short string (the “needle”) and attempts to find it in a larger body of text (the “haystack”) using approximate search [elonen/php-approximate-search](https://github.com/elonen/php-approximate-search).

## API

Possible endpoints

- citation/parse

- container/issn
- container/abbreviation

- text/find

- work
 
