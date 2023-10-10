## WordPress Carbon Calculator

The WordPress Carbon Calculator, drawing inspiration from the acclaimed Website Carbon Calculator algorithm 2.0 and leveraging The Green Web Foundation's co2.js, is a powerful plugin tailored to empower website owners in their quest to assess and minimize their carbon footprint. 

This user-friendly tool allows you to effortlessly calculate the CO2 impact of any page on your website directly from your WordPress admin panel.

Whether you're a sustainability-conscious blogger, a corporate website manager, or anyone committed to environmental responsibility, the WordPress Carbon Calculator is your essential solution for environmental impact assessment.

### Key Features

- **Precision Carbon Calculations**: Built upon the foundation of the Website Carbon Calculator algorithm 2.0 and powered by The Green Web Foundation's co2.js, our plugin delivers highly accurate CO2 impact calculations by incorporating the latest environmental data.

- **Intuitive Interface**: The WordPress Carbon Calculator offers an intuitive and user-friendly interface within your WordPress admin area, simplifying the process of accessing and analyzing environmental data.

- **Front-End Presentation**: Showcase the computed CO2 impact prominently on your website's front end, allowing you to engage and educate your website visitors.

- **Data from Google Page Speed**: The plugin gathers loaded data efficiently using Google Page Speed, ensuring you have access to comprehensive performance metrics to make informed decisions.

### Installation

#### Via Composer

```shell
$ composer require akhela/wp-carbon-calculator
```
#### Via GitHub

1. Download the plugin as a ZIP file from the GitHub repository.
2. Upload the ZIP file to your WordPress site by navigating to the Plugins section in your WordPress admin panel and clicking on "Add New."

#### Getting Started

1. Activate the WordPress Carbon Calculator plugin.
2. Navigate to "Settings" and select "Carbon Calculator."
3. Enter the necessary information to configure the plugin.

**Note:** The carbon calculator functionality is not available in a local development environment.

### Interface

**Summary**

![Summary](https://github.com/akhela-studio/wp-carbon-calculator/assets/4919596/da38f0a5-e028-4d86-a070-b7cb548ceebc)

**Calculator**

![Calculator](https://github.com/akhela-studio/wp-carbon-calculator/assets/4919596/3bd53fe8-3c33-4628-afff-1f447727d331)

**Tools**

![Tools](https://github.com/akhela-studio/wp-carbon-calculator/assets/4919596/2eb6d38e-0f5e-473a-9245-7381a6f3fb55)

**Settings**

![Settings](https://github.com/akhela-studio/wp-carbon-calculator/assets/4919596/381d6e2f-48fd-4250-8c85-b331ea25fe57)

### How to display it in your templates ?

Integrating the WordPress Carbon Calculator into your website's front-end templates is a breeze and allows you to display the calculated carbon emissions directly to your site visitors. 

In your front-end template files (such as your theme's template files or custom templates), you can access the carbon calculation method by using ```get_calculated_carbon()```. This method is available on any page of your site.

Here's an example of how you can use the code:

```php

<?php if($calculated_carbon = get_calculated_carbon() ): ?>
This page emits <?=round($calculated_carbon,2)?>g eq. CO₂
<?php endif; ?>

```

The code snippet will display the calculated carbon emissions on the front-end of your website for your site visitors to see. It provides valuable information about the environmental impact of the current page.

By following these simple steps, you can raise awareness about the carbon footprint of your web content and encourage your audience to make more sustainable choices.

Feel free to customize the code or incorporate it into your templates as needed to fit your website's design and layout.

Remember, this feature adds a powerful environmental dimension to your website, aligning it with eco-conscious principles and contributing to a greener digital landscape.

### Roadmap

Here's a glimpse of what's coming:

- translations
- ecoindex.fr algorithm support
- better settings/tools interface