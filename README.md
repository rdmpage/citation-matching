# Citation matching

Tools to match short citations to cited works, such as those that occur in taxonomic databases and publications (“microcitations”). Typically these citations are to one or more pages in a work, not the complete work itself. For work on parsing full citations of works (such as appear in the reference list at the end of a paper) see, for example [rdmpage/citation-parsing](https://github.com/rdmpage/citation-parsing).

Microcitations are equivalent to what the legal profession terms  “pinpoint citation” or “pinches” (see [What are pincites, pinpoints, or jump legal references?](https://rasmussen.libanswers.com/faq/283203)).

The goals of this work are:
- parse short citations into structured data
- match short citations to the complete work (e.g., a paper with a DOI)
- match short citations to their digital equivalent (e.g., the page in the Biodiversity Heritage Library).

The focus will be on a set of APIs that can be used to process data, rather than a web interface.


## API


## Using

The script `php client_to_json.php` will process a TSV file that must have at least columns headed `scientificname` and `citation`. It will attempt to parse these and output detailed results in a JSON file. This file can be then be output as HTML using `json_to_html.php` or TSV using `json_to_tsv.php`.


## Notes

### Parsing

The parser uses regular expressions. The output includes XML-style tagging so that if we move to approaches such as CRF we can easily generate training data.

Need to be able to parse lists of pages, plates, figures, etc., See `collation_parser.php` for ideas. The term “collation” is borrowed from IPNI.

### Matching containers

Microcitations often have abbreviated journal names. Ideally these “strings” will be matched to “things” with identifiers, such as ISSNs.  

### ISSNs

The [International Standard Serial Number](https://en.wikipedia.org/wiki/International_Standard_Serial_Number) (ISSN) is a standard identifier for journals. Based on previous projects I have assembled a set of journal names and abbreviations and the corresponding ISSNs. These can be found as CSV files in the `database/issn` directory. The file `import-issn.php` will import these into the database `matching.db`.

The file `extra.tsv` contains a list of titles that can be added to as we discover journals and/or abbreviations that are missed.

### BHL

BHL has a data dump matching external identifiers such as ISSNs to BHL’s own title identifiers (`titleidentifier.txt`). The file `import-bhl.php` will import these into the database `matching.db`.

If an article potentially exists in BHL we can use the BHL OpenURL resolver to locate the corresponding page (or pages). Note that there may be more than one matching page if BHL has multiple scans of the same volume, or a scan comprising multiple volumes.

### BHL triple store 

Experimenting with BHL in RDF to help resolve (journal, volume, page) triples, as well as discover parts that include pages, etc. 

### Journal abbreviations

[ISO 4](https://en.wikipedia.org/wiki/ISO_4) defines rules for abbreviating titles. There is a file of abbreviations available from ISSN.org: https://www.issn.org/wp-content/uploads/2021/07/ltwa_20210702.csv. The file `import-ltwa.php` will import this CSV file into a SQLite database called `matching.db`;

### Text matching

[Microcitations: linking nomenclators to BHL](https://iphylo.blogspot.com/2011/03/microcitations-linking-nomenclators-to.html) discussed validating matches between microcitations and BHL by using associated taxonomic names. If we have a taxonomic name and a citation, one way to valid that we’ve matched the citation to the correct page in BHL is to see whether that page mentions the taxonomic name. The presence of OCR errors means we will need to use approximate matching to check for the presence of the taxon name.

The `api_text.php` service takes a short string (the “needle”) and attempts to find it in a larger body of text (the “haystack”) using approximate search [elonen/php-approximate-search](https://github.com/elonen/php-approximate-search). Matches found have their text coordinates recorded as well as flanking regions of text, so they can be represented as annotations.

