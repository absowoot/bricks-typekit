# Instructions

1. Upload typekit.php to your child theme.
2. Update your functions.php to include the file, example:

        include get_stylesheet_directory() .'/typekit.php';
3. While editing in Bricks, go to **Theme Settings** and click on the **Typography** section. You should see an input for Typekit Project ID
4. Paste your Typekit Project ID ([found in your Typekit account](https://fonts.adobe.com/my_fonts#web_projects-section))
5. Click the Sync Typekit Now button

# Notes
- Typekit updates are not automatically synced. If you update your Typekit project, you will need to click the Sync Typekit Now button to get the latest updates
- Due to current limitation to how data is saved by Bricks, each Typekit project will create a new row in the options table. Soon this will be integrated into the 'bricks_theme_styles option
- Saved fonts in your Wordpress database will not delete when you delete the theme. This will be added soon.
