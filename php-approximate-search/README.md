Approximate/fuzzy string search in PHP
======================================

This PHP class, `approximate-search.php`, provides non-exact text search (often called fuzzy search or approximate matching).

It allows you to specify a Levenshtein edit distance treshold, i.e. an error limit for a match. For example, a search for kamari with a treshold of 1 errors would match `kamari`, `kammari` (1 add), `kaNari` (1 change) and `kamar` (1 delete) but not `kaNar` (1 change + 1 delete).

The code is optimized for repeated searching of the same string, e.g. walking through rows of a database.

Usage example
=============

```
$search = new Approximate_Search( $patt, $max_err );
if ( $search->too_short_err )
    $error = "Unable to search - use longer pattern " .
             "or reduce error tolerance.";

while( $text = YOUR_FUNCTION_TO_GET_MORE_TEXT_TO_SEARCH())
{
    $matches = $search->search( $text );
    while( list($i,) = each($matches))
      print "Match that ends at $i.\n";
}
```


Algorithm
=========

The program consist of two phases and some optimizations. Without going into details, here's a short overview of the idea:

> Suppose we have an _n_ character long search pattern with _k_ errors allowed.
> 
> 1.  **Pruning / filtering**. First partition the search pattern into k+2 parts. By the pigeonhole principle (of combinatorics), at least 2 of these parts must be uncorrupted in each occurrence of the pattern. We search the full text for these parts first and discard immediately those portions of the text that don't contain at least 2 of the patterns sufficiently near each other and in the correct order.
> 2.  **Fine search**. For the remaining candidates, do a finer search using an (n+1) x (k+1) grid shaped nondeterministic state machine (NFA) whose horizontal edges represent character matches, vertical edges additions and diagonal edges deletions. This one's a bit difficult to explain without some drawings, so I'd suggest you take a look at, for example, _Baeza-Yates & Navarro: "Faster Approximate String Matching"_. It describes a very low level optimization method that I'm not using (as it would probably be _slower_ in PHP) but it also explains the basic version quite well.

MIT License
===========

Copyright 2003-2006 Jarno Elonen <elonen@iki.fi>

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
