# Multisite Privacy

**INACTIVE NOTICE: This plugin is unsupported by WPMUDEV, we've published it here for those technical types who might want to fork and maintain it for their needs.**

## Translations

Translation files can be found at https://github.com/wpmudev/translations

## Multisite Privacy adds network wide privacy levels and allows you to decide whether users can override them.

### More levels, greater control

This plugin gives you full control over privacy on your network.  

##### It’s everything you'll need, plus a whole lot more:

*   Adds four new privacy options to Settings > Privacy in the site admin dashboard.
*   Choice of which privacy options are made available to your users!
*   Hassle free interface.  Changing privacy across your network is as simple as updating your settings in the network admin dashboard.
*   Ability to let users select their preferred privacy option when signing up for their new site.
*   Control the default privacy setting of all new sites created on your network.
*   Easily update the privacy settings of all site across your entire network at the same time!
*   Control users ability to override default privacy settings
*   Works perfectly with Multisite and BuddyPress.
*   Use this plugin on any WordPress project you like.

##### Here's your four new privacy options:

1.  Allow any registered users on the network to view site.
2.  Allow only subscribers or users of the site to view it.
3.  Allow access to only administrators of the site - great for testing purposes.
4.  Require a single password to access the site - allowing only those you want to view the site but without the need for them to have a user account!

### Smart settings

Toggle options in the admin dashboard for quick setup. 

![network-privacy-2](http://premium.wpmudev.org/wp-content/uploads/2009/02/network-privacy-2.png)

 Easy privacy configuration

   Multisite Privacy gives you more control without the hassle of setting up a full [membership](http://premium.wpmudev.org/project/protected-content/) plugin.

## Usage

![network-privacy](https://premium.wpmudev.org/wp-content/uploads/2009/02/network-privacy.png "Privacy settings in Settings in the network admin dashboard")

#### Show Privacy options at sign up

Show Privacy option at sign up is displayed when either 'Logged in users may register new sites' or 'Both sites and user accounts can be registered' is selected under Registration Settings in **Settings  > Network Settings** in the network admin dashboard. When 'Yes' is selected users are able to set their preferred privacy options as their site is created. Here's what the privacy options look like on the sign up page when 'Yes' is selected: 

![image](https://premium.wpmudev.org/wp-content/uploads/2009/02/privacy62.jpg "Selecting privacy when signing up for a new site")

#### Available Options

Available options allows the Super admin user to control which of the privacy options are displayed in **Settings > Reading** in the site admin dashboard. Here's what the privacy options look like when all are selected in network settings: 

![network-privacy-2](https://premium.wpmudev.org/wp-content/uploads/2009/02/network-privacy-2.png "Privacy options in Privacy in the site admin dashboard")

#### Default Settings

Default settings control the Privacy setting of all newly created sites. For example, if you wanted to make all newly created sites private you would choose either:

1.  **Only allow logged in users to see all blogs** - any one who is a registered user on your network, and who is logged into their account will be able to view the site.  Ideal if you want to make sites private without adding users to individual sites.
2.  **Only allow a registered user to see a blog for which they are registered to** -  any one who is a registered user of the site and who is logged into their account will be able to view the site .i.e anyone who has been added as a user to site and is listed in Users > All Users.  Used when you want to limit access to specific people.
3.  **Only allow administrators of a blog to view the blog for which they are an admin** -  any one who is a registered admin user of the site and who is logged into their account will be able to view the site.  Ideal for testing purpose before making a site public.

#### Allow Override

Allow Override lets you decide if blog admin users are able to change privacy options in Settings > Reading. For example, you would select 'No' if you needed to keep all sites private.

#### Update All Sites

Update all Sites is designed to update the privacy settings on all sites across your network. For example, you would use Update all Sites if your default setting was 'Allow all visitors to all blogs' and you needed to make all sites private quickly.

### Please Note:

1.  Select ‘Update All Sites’ to update existing blogs and apply to all new ones being created.
2.  Don’t select ‘Update All Blogs’ if you only want new privacy defaults to apply to all new sites being created.
3.  Update All Sites does not update the Privacy Settings of the main site.  This is because it is common to make the main site 'Allow all visitors to all blogs' so you can provide community support and help on the main site.  To change the Privacy setting of the main site you need to go to Settings > Reading in the site admin dashboard of the main site.

### Changing Privacy Settings on a site by site basis

You can change the privacy of a site by: 1.  Logging into the site admin dashboard of the site 2.  Go to **Settings > Reading** 3.  Select your new privacy setting. 4.  Click **Save Changes**. 

![image](https://premium.wpmudev.org/wp-content/uploads/2009/02/privacy64.jpg "Changing privacy in the site admin dashboard")
