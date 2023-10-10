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

**Tools**

![Tools](https://github.com/akhela-studio/wp-carbon-calculator/assets/4919596/7deaf40e-22e7-4316-8c51-893c1e3709e4)

**Settings**

![Settings](https://github.com/akhela-studio/wp-carbon-calculator/assets/4919596/0014696b-a295-4d49-aac5-6b039bf0097e)

**Summary**

![Summary](https://github.com/akhela-studio/wp-carbon-calculator/assets/4919596/7fbc18c0-9b35-4e4e-9a44-3fa6684299a1)

**Calculator**

![Calculator](https://github.com/akhela-studio/wp-carbon-calculator/assets/4919596/d7a45b4c-d6eb-4357-b7fa-7d5e9f29b24d)

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