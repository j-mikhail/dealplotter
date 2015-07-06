![DealPlotter](/Logo.png)

At the peak of the group buy phenomenon and ahead of the dramatic implosion and consolidation of the space, I built a localized aggregation portal, focused on presenting nearby deals in relation to the street-level position of site visitors, akin to the Groupon app which later followed. I further leveraged third party APIs to present historical data and supplemental metadata on vendors to better determine deal value and credibility.

The site was built exclusively in HTML, CSS, PHP and MySQL and many lessons were learned throughout its evolution, both from technical and business perspectives. As a personal project, I didn't expect much in the terms of exposure; nevertheless, I underestimated the importance of a strong social media presence, and neglected to make the site mobile responsive.

Most of the code is self-taught using books, and as such, may not follow best established practices. Nevertheless, a lot of time and effort was put in it, and it all worked quite brilliantly for a while, so I'm releasing it as a teaching aid for anybody who might find it useful.

## Features

* All custom graphic design work. No font packs were used.
* Customizable deal tracking with mailed digest options.
* Custom parser to crawl deal sites, as opposed to restrictive or missing APIs.
* Vendor tracking for deals repeated over time and across sites.
* Generated SVG histograms of vendor deal prices [(Example)](/Hist/2c2c75f9710f6abd8bb9f3aa1e176f02.svg)
* QR code generation for mobile deal tracking.
* Lightweight, compact for quite loading.
* Admin interface for ease of maintenance.

## Requirements

* LAMP stack or equivalent.

## Folder structure

```
.
├── /Code/         # Main PHP display/processing/administration files
│   └── /Sources/  # Crawlers for specific deal sites
├── /Hist/         # Generated SVG histograms
├── /IF/           # Graphics
│   └── /Old/      # Obsoleted graphics
├── /Pages/        # Source HTML for deal sites, at time of last crawl (late 2011/early 2012)
├── /QR/           # Generated QR Codes
└── /Scripts/      # CSS and JS code
    └── /Crushed/  # Compacted code for production
```

##License

Open Source. MIT © Jonathan Mikhail