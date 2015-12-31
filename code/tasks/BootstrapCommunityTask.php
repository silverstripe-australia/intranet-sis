<?php

/**
 * @author <marcus@silverstripe.com.au>
 * @license BSD License http://www.silverstripe.org/bsd-license
 */
class BootstrapCommunityTask extends BuildTask
{

    public function run($request)
    {
        Restrictable::set_enabled(false);
        Versioned::reading_stage('Stage');

        $admin = Security::findAnAdministrator();
        Session::set("loggedInAs", $admin->ID);

        $toPublish = array();

        $home = SiteTree::get()->filter('URLSegment', 'home')->first();
        if ($home) {
            $this->o("Home page already exists, _not_ bootstrapping");
            return;
        }

        $site = Multisites::inst()->getCurrentSite();
        $toPublish[] = $site;

        $dashboard = SiteDashboardPage::create(array(
                'Title' => 'Dashboard',
                'URLSegment' => 'dashboard',
                'ParentID' => $site->ID
        ));
        $dashboard->write();
        $this->o("Created Dashboard");
        $toPublish[] = $dashboard;

        $home = RedirectorPage::create(array(
                'Title' => 'Home',
                'URLSegment' => 'home',
                'ParentID' => $site->ID
        ));
        $home->LinkToID = $dashboard->ID;
        $home->write();
        $toPublish[] = $home;
        $this->o("Created homepage");

        $group = Group::create(array(
                'Title' => 'All members',
        ));

        $events = Calendar::create(array(
                'Title' => 'Events',
                'URLSegment' => 'events',
                'ParentID' => $site->ID
        ));

        $events->write();
        $toPublish[] = $events;

        $dummyEvent = CalendarEvent::create(array(
                'Title' => 'Sample event',
                'ParentID' => $events->ID,
        ));
        $dummyEvent->write();
        $toPublish[] = $dummyEvent;

        $dateTime = CalendarDateTime::create(array(
                'StartDate' => strtotime('+1 week'),
                'AllDay' => 1,
                'EventID' => $dummyEvent->ID,
        ));
        $dateTime->write();
        
        $files = FileListingPage::create(array(
                'Title' => 'File Listing',
                'ParentID' => $site->ID,
        ));
        $files->write();
        $toPublish[] = $files;

        $news = MediaHolder::create(array(
                'Title' => 'News',
                'MediaTypeID' => 3,
                'ParentID' => $site->ID,
        ));
        $news->write();
        $toPublish[] = $news;

        $text = <<<WORDS
			<p>Oh no! Pull a sickie, this epic cuzzie is as rip-off as a snarky morepork. Mean while, in behind the 
				bicycle shed, Lomu and The Hungery Caterpilar were up to no good with a bunch of cool jelly tip icecreams. 
					The flat stick force of his chundering was on par with Rangi's solid rimu chilly bin. Put the jug on 
			will you bro, all these hard yakka utes can wait till later. The first prize for frying up goes to... 
							some uni student and his wicked wet blanket, what a egg. Bro, giant wekas are really tip-top good
		with dodgy fellas, aye. You have no idea how nuclear-free our bung kiwis were aye. Every time</p><p>
						I see those carked it wifebeater singlets it's like Castle Hill all over again aye, pissed 
										as a rat. Anyway, Uncle Bully is just Mr Whippy in disguise, to find the true meaning of 
											life, one must start whale watching with the box of fluffies, mate. After the trotie
												is jumped the ditch, you add all the heaps good whitebait fritters to 
													the paua you've got yourself a meal.</p><p>Technology has allowed
														mint pukekos to participate in the global conversation of
															choice keas. The next Generation of pearler dole bludgers have already packed a sad over at the beach. What's the hurry The Topp Twins? There's plenty of twink sticks in that one episode of Tux Wonder Dogs, you know the one bro. The sausage sizzle holds the most sweet as community in the country.. A Taniwha was playing rugby when the random reffing the game event occured. Those bloody Jaffa's, this outrageously awesome seabed is as tapu as a naff bloke. Pavalova is definitely not Australian, you don't know his story, bro. Mean while, in the sleepout, Jim Hickey and Sir Edmond Hillary were up to no good with a bunch of beautiful whanaus. The stuffed force of his cruising for a brusing was on par with James Cook's pretty suss pikelet. Put the jug on will you bro, all these buzzy stubbiess can wait till later.</p><p>The first prize for preparing the hungi goes to... Bazza and his rough as guts pohutukawa, what a sad guy. Bro, Monopoly money, from the New Zealand version with Queen Street and stuff are really hard case good with stink girl guide biscuits, aye. You have no idea how thermo-nuclear our sweet as mates were aye. Every time I see those fully sick packets of Wheetbix it's like Mt Cook all over again aye, see you right. Anyway, Mrs Falani is just Jonah Lomu in disguise, to find the true meaning of life, one must start rooting with the milk, mate. After the native vegetable is munted, you add all the beached as pieces of pounamu to the cheese on toast you've got yourself a meal. Technology has allowed primo kumaras to participate in the global conversation of sweet  gumboots. The next Generation of beaut manuses have already cooked over at Pack n' Save. What's the hurry Manus Morissette? There's plenty of onion dips in West Auckland. The tinny house holds the most same same but different community in the country.. Helen Clarke was packing a sad when the pretty suss whinging event occured. Eh, this stoked hongi is as cracker as a kiwi as chick.</p><p>Mean while, in the pub, Hercules Morse, as big as a horse and James and the Giant Peach were up to no good with a bunch of paru pinapple lumps. The bloody force of his wobbling was on par with Dr Ropata's crook lamington. Put the jug on will you bro, all these mean as foreshore and seabed issues can wait till later. The first prize for rooting goes to... Maui and his good as L&amp;P, what a hottie. Bro, marmite shortages are really shithouse good with hammered toasted sandwiches, aye. You have no idea how chocka full our chronic Bell Birds were aye. Every time I see those rip-off rugby balls it's like smoko time all over again aye, cook your own eggs Jake. Anyway, Cardigan Bay is just Spot, the Telecom dog in disguise, to find the true meaning of life, one must start pashing with the mince pie, mate.</p>
			
WORDS;

        $story = MediaPage::create(array(
            'Title' => 'Sample news item',
            'Content' => $text,
            'ParentID'        => $news->ID,
        ));
        $story->write();
        $toPublish[] = $story;

        $group->write();
        $this->o("Created All Members group");

        
        $member = Member::create(array(
            'FirstName' => 'Anon',
            'Surname'    => 'Ymous',
            'Email'        => 'anon@test.com'
        ));
        $member->write();
        $member->Groups()->add($group);
        
        
        $site->Theme = 'ssau-minimalist';
        $site->LoggedInGroups()->add($group);
        $site->write();
        $this->o("Configured Site object");

        foreach ($toPublish as $item) {
            if (!is_object($item)) {
                print_r($item);
                continue;
            }
            $item->doPublish();
        }
        $this->o("Published everything");
        
        $message = <<<MSG
Your community system has been succesfully installed! Some things you might be interested in doing from this point are...

* Replying to this post! 
* Customising your dashboard
* Uploading some files and images to browse in the [file listing](file-listing)
* Create some events
* Add some RSS feeds to your Announcements dashlet (use the wrench to configure it!)
MSG;
        
        singleton('MicroBlogService')->createPost(null, $message, 'Installed!', 0, null, array('logged_in' => 1));

        Restrictable::set_enabled(true);
    }

    private function o($txt)
    {
        if (PHP_SAPI == 'cli') {
            echo "$txt\n";
        } else {
            echo "$txt<br/>\n";
        }
    }
}
