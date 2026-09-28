<?php
/**
 * Health Hub launch set: six stories, created once in WordPress as DRAFTS.
 *
 * Nothing here is published. Each draft waits in Posts → Drafts for a
 * pharmacist to review it, set "Reviewed by", add a photograph and publish
 * (see docs/health-hub.md). Once three are published the home page section
 * appears by itself.
 *
 * Runs once (option ah_health_hub_launch_drafts_v1). A story whose slug
 * already exists — in any state — is skipped, so nothing is ever duplicated
 * or overwritten; after the first run WordPress owns the words.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function ah_hh_launch_stories() {
    return array(

        array(
            'slug'    => 'eat-well-for-less',
            'section' => 'eat-well',
            'title'   => 'Eat well for less',
            'excerpt' => 'Healthy food has a reputation for being expensive. It doesn’t have to be. A few habits in the supermarket and the kitchen make more difference than any superfood.',
            'content' => <<<'HTML'
<p>There’s a moment in the supermarket most of us know. You pick up the salmon, look at the price, and put it back. Then you walk past the meal deals, three for a fiver, and wonder whether eating well is only for people with bigger budgets.</p>
<p>It isn’t. Some of the most filling, nourishing food in the shop is also the cheapest. The trick is knowing where to look, and building a few habits that stop money, and food, ending up in the bin.</p>

<h2>Start with a plan, not a basket</h2>
<p>Unplanned shops cost more. Before you go, check what’s already in the fridge and cupboards, plan four or five evening meals around it, and write a list. It sounds obvious because it works: you buy less on impulse, and less goes off before you get to it.</p>
<ul>
<li><strong>Plan meals that share ingredients.</strong> A bag of spinach can go into an omelette on Monday and a curry on Wednesday.</li>
<li><strong>Shop after you’ve eaten.</strong> Hungry shopping is expensive shopping.</li>
<li><strong>Check the price per kilo</strong> on the shelf label, not the price on the pack. Bigger isn’t always cheaper.</li>
</ul>

<h2>The cheap foods that do the most work</h2>
<p>Protein and fibre are what keep you full between meals, and both come cheaply once you know where to find them.</p>
<ul>
<li><strong>Eggs.</strong> One of the lowest-cost sources of good-quality protein, and ready in minutes.</li>
<li><strong>Tinned beans, lentils and chickpeas.</strong> Protein and fibre together, no soaking needed. Rinse them to cut the salt.</li>
<li><strong>Tinned fish.</strong> Sardines, mackerel and tuna keep for months and make a meal in no time.</li>
<li><strong>Frozen vegetables and fruit.</strong> Frozen soon after picking, so they keep their goodness, and none of it wilts at the back of the fridge. They count towards your five a day.</li>
<li><strong>Oats.</strong> A filling breakfast that costs very little per bowl.</li>
<li><strong>Own-brand staples.</strong> Supermarket own-label tins, frozen veg and dairy are often every bit as good as the big brands. Try dropping a brand level on the basics and see whether you notice.</li>
</ul>

<h2>Cook once, eat twice</h2>
<p>Batch cooking is where the biggest savings are. A large pot of chilli, soup or stew costs little more to make than a small one, and the extra portions go in the freezer for the evenings when you’d otherwise reach for a takeaway.</p>
<ul>
<li>Label and date what you freeze, so it gets eaten.</li>
<li>Freeze in single portions. They’re easier to grab, and easier to stop at.</li>
<li>Bulk out meat dishes with lentils, beans or extra vegetables. Half the mince, same size pot.</li>
</ul>

<h2>Waste less, spend less</h2>
<p>UK homes throw away millions of tonnes of food every year, and a lot of it could have been eaten. Every bit you save is money back in your pocket.</p>
<ul>
<li>Keep a “use first” shelf in the fridge for anything close to its date.</li>
<li>Know your dates. “Use by” is about safety: don’t eat food after it. “Best before” is about quality: food is usually fine after it, if it looks and smells right.</li>
<li>Turn leftovers into lunches. Last night’s roasted vegetables make tomorrow’s wrap.</li>
<li>Freeze bread and toast it from frozen.</li>
</ul>

<h2>What a day could look like</h2>
<ul>
<li><strong>Breakfast:</strong> porridge made with milk, topped with frozen berries.</li>
<li><strong>Lunch:</strong> a bowl of lentil and vegetable soup from the weekend’s batch, with a slice of wholemeal bread.</li>
<li><strong>Dinner:</strong> a vegetable-packed chilli made with half mince, half beans, with rice.</li>
<li><strong>If you’re peckish:</strong> a yoghurt, a piece of fruit or a boiled egg.</li>
</ul>

<h2>One habit at a time</h2>
<p>Eating well on a budget isn’t about finding the perfect meal plan. It’s a handful of small habits, repeated until you don’t think about them. Pick one this week (the list, the batch cook, the “use first” shelf) and let it settle in before you add the next.</p>
<p>Your bank balance won’t notice any single change. It will notice all of them together.</p>
HTML
        ),

        array(
            'slug'    => 'become-a-weight-loss-super-sleuth',
            'section' => 'eat-well',
            'title'   => 'Become a weight loss super sleuth',
            'excerpt' => 'Food packaging is full of clues, and a few of them are there to mislead you. Here’s how to read a label like a detective, in the time it takes to push a trolley down the aisle.',
            'content' => <<<'HTML'
<p>Somewhere between “high protein”, “low fat” and “one of your five a day”, the front of a food packet can start to feel like a crime scene. Everyone’s making claims. Not all of them stand up.</p>
<p>The good news is that you don’t need a nutrition degree to crack the case. You need to know where the evidence is kept, and which clues to trust.</p>

<h2>Clue one: the traffic lights</h2>
<p>Many packs in the UK carry a front-of-pack label that colours fat, saturated fat, sugars and salt red, amber or green. It’s the quickest clue you’ve got. Mostly green and amber is a good sign. A red isn’t a reason never to buy something; it’s a reason to notice how much you have, and how often.</p>
<p>If a pack doesn’t have traffic lights, the nutrition table on the back does the same job. Look at the “per 100g” column and use these as a guide:</p>
<ul>
<li><strong>Fat:</strong> low is 3g or less; high is more than 17.5g.</li>
<li><strong>Saturated fat:</strong> low is 1.5g or less; high is more than 5g.</li>
<li><strong>Sugars:</strong> low is 5g or less; high is more than 22.5g.</li>
<li><strong>Salt:</strong> low is 0.3g or less; high is more than 1.5g.</li>
</ul>

<h2>Clue two: per 100g beats per portion</h2>
<p>Portion sizes on packs are chosen by the manufacturer, and they’re often smaller than what people eat. The “per 100g” column lets you compare two products fairly, side by side, whatever size they come in. Comparing two cereals, two yoghurts or two ready meals? Compare the 100g column.</p>
<p>When you do use the per-portion figures, check what the portion is. A “portion” of crisps might be half the bag.</p>

<h2>Clue three: the ingredients list gives the order of events</h2>
<p>Ingredients are listed from most to least. If sugar, or one of its aliases, is in the first three, it’s a big part of what you’re buying.</p>
<p>Sugar goes by a lot of names. Look out for glucose, fructose, sucrose, dextrose, maltose, honey, agave, fruit juice concentrate, and anything ending in “syrup”. A product can spread its sugar across several of these, so that no single one sits near the top.</p>

<h2>Clue four: beware the health halo</h2>
<p>Some words on the front of a pack are there to make you feel good about buying it.</p>
<ul>
<li><strong>“Low fat”</strong> can come with extra sugar, added to put the flavour back.</li>
<li><strong>“Natural”</strong> has no set meaning for most foods.</li>
<li><strong>“High protein”</strong> snack bars can carry as much sugar as a chocolate bar.</li>
<li><strong>“No added sugar”</strong> doesn’t mean low sugar. Fruit juice and dried fruit are naturally sugary.</li>
<li><strong>“Light”</strong> only means lower than a standard version of the same food. If the standard version is very high, the light one can still be high.</li>
</ul>

<h2>Clue five: check your drinks</h2>
<p>Drinks are the easiest place for sugar and calories to hide, because they don’t fill you up. Read the labels on juices, smoothies, flavoured coffees and “vitamin” waters. Water, milk, and tea or coffee without sugar are the safest choices.</p>

<h2>Case closed</h2>
<p>You won’t need to interrogate every packet for ever. After a few weeks of checking, you’ll know your usual shop by heart, and you’ll only need a closer look at new things.</p>
<p>That’s the moment you’ve become the sleuth: fewer surprises, better choices, and a lot less guesswork every time you shop.</p>
HTML
        ),

        array(
            'slug'    => 'the-adventures-are-just-beginning',
            'section' => 'real-life',
            'title'   => 'Weight loss. The adventures are just beginning.',
            'excerpt' => 'It’s easy to put life on hold until you reach a number on the scales. But the best parts of this often turn up long before the finish line, if you let them.',
            'content' => <<<'HTML'
<p>There’s a sentence a lot of us have said, out loud or quietly. <em>I’ll do that when I’ve lost the weight.</em></p>
<p>The swim. The holiday photos. The dance class. The walk up the hill everyone else does without thinking. We save them for later, for a version of ourselves who’s lighter, fitter, more deserving.</p>
<p>Here’s the thing about later. It keeps moving.</p>

<h2>Stop waiting for the finish line</h2>
<p>Changing your weight takes time, and it doesn’t happen in a straight line. If everything good is saved for the end, the months in between can feel like a waiting room. But those months are your life too, and the small adventures you have along the way are part of what keeps you going.</p>

<h2>Notice the wins the scales can’t see</h2>
<p>The number on the scales is one measure, and a fickle one. Some of the changes that matter most don’t show up there at all:</p>
<ul>
<li>Getting to the top of the stairs without stopping for breath.</li>
<li>A belt that needs a new notch.</li>
<li>Sleeping better, and waking up with more energy.</li>
<li>Getting down on the floor to play, and back up again, without thinking twice.</li>
<li>Saying yes to an invitation you’d once have found a reason to avoid.</li>
</ul>
<p>Write them down as they happen. On a flat week, that list is worth more than any pep talk.</p>

<h2>Start a small-adventures list</h2>
<p>Make a list of the things you’ve been putting off, and keep them small enough to do soon: a new walk, a class you’ve been curious about, a day trip, a swim, cooking something you’ve never tried. Then pick one this month, and do it at the size you are now.</p>
<p>You don’t have to love it. The point isn’t to become a different person overnight. It’s to stop postponing your own life while you work on your health.</p>

<h2>Be kind about the wobbles</h2>
<p>There will be weeks that feel hard, and weekends that don’t go to plan. That isn’t failure. It’s what every long journey looks like up close. What matters is the direction over months, not the detail of one Saturday.</p>
<p>Pick things back up at the next meal, not next Monday.</p>

<h2>The view from here</h2>
<p>When you look back on this, it’s unlikely to be the finish line you remember most. It’ll be the trip you finally booked, the class you joined, the photo you got into instead of taking.</p>
<p>Wherever you are right now, at the very start or somewhere in the middle, the adventures don’t begin at the finish line. They begin with the next thing you say yes to.</p>
HTML
        ),

        array(
            'slug'    => 'exercise-for-people-who-would-rather-not',
            'section' => 'move-more',
            'title'   => 'Exercise for people who’d rather not',
            'excerpt' => 'If the word “workout” makes you want to sit down, this one’s for you. Being more active doesn’t have to mean a gym, Lycra, or anyone watching.',
            'content' => <<<'HTML'
<p>Some people love exercise. They talk about it at parties. They own specialist socks.</p>
<p>If that isn’t you, you’re in good company, and you don’t need to become that person to get the benefits of moving more. You need a kind of activity you don’t dread, in a dose small enough that you’ll do it.</p>

<h2>What counts (more than you’d think)</h2>
<p>The NHS recommends that adults aim for at least 150 minutes of moderate activity a week, plus activities that strengthen the muscles on at least two days. “Moderate” means you’re breathing faster and feel warmer, but can still hold a conversation.</p>
<p>150 minutes sounds a lot until you break it down. It’s a little over 20 minutes a day, and it can come in 10-minute pieces. Brisk walking counts. So does cycling to the shops, dancing in the kitchen and pushing a lawnmower.</p>

<h2>Start smaller than feels useful</h2>
<p>One of the most common reasons people give up is starting too big. A plan that asks for an hour a day falls apart in the first busy week. A 10-minute walk after lunch survives almost anything.</p>
<ul>
<li>Walk for 10 minutes after one meal a day. Add a minute or two each week.</li>
<li>Take the stairs for one flight, then two.</li>
<li>Get off the bus a stop early, or park at the far side of the car park.</li>
<li>Get up and move between episodes, instead of letting the next one start.</li>
</ul>

<h2>Why strength matters too</h2>
<p>When you lose weight, you want as much of it as possible to come from fat rather than muscle. Keeping your muscles working helps with that, and strong muscles make everyday life easier, from carrying the shopping to getting up out of a chair.</p>
<p>You don’t need weights to start. Try these at home, a few of each, two or three days a week:</p>
<ul>
<li><strong>Sit-to-stands</strong> from a sturdy chair, without using your hands if you can.</li>
<li><strong>Wall press-ups</strong>, hands on the wall at shoulder height.</li>
<li><strong>Step-ups</strong> on the bottom stair, holding the banister.</li>
<li><strong>Carrying the shopping</strong> a little further than you need to.</li>
</ul>

<h2>Find the one you don’t hate</h2>
<p>Enjoyment beats intensity, because it’s what gets you out of the door next week. If walking alone is dull, walk with a friend or a favourite podcast. If you like water, try a swim or an aqua class. If you like company, look for a beginners’ dance class, a local walking group, or parkrun, where plenty of people walk the whole way.</p>

<h2>A few sensible checks</h2>
<p>If you have a heart condition, joint problems or another health condition you’re unsure about, or you haven’t been active for a long time, check with your GP before starting something new. Stop and get medical advice if you have chest pain, feel dizzy or faint, or are unusually short of breath.</p>

<h2>Thursday is the goal</h2>
<p>You don’t need to love exercise. You need something you’ll do again on Thursday. Start with ten minutes, and let the habit do the heavy lifting.</p>
HTML
        ),

        array(
            'slug'    => 'when-the-scales-stop-moving',
            'section' => 'mind',
            'title'   => 'When the scales stop moving',
            'excerpt' => 'You did everything right this week and the number hasn’t budged, or it’s gone up. Before you decide it isn’t working, here’s what’s going on.',
            'content' => <<<'HTML'
<p>You stuck to it. You planned your meals, went for the walks, turned down the biscuits. And this morning the scales said the same as last week. Or more.</p>
<p>It’s one of the most deflating moments there is. It’s also one of the most misunderstood.</p>

<h2>Your weight moves every day, for reasons that aren’t fat</h2>
<p>Body weight can rise and fall by a kilo or more from one day to the next, with no change in body fat at all. The usual reasons:</p>
<ul>
<li><strong>Water.</strong> A salty meal, a hot day, or more carbohydrate than usual can make your body hold on to extra water for a day or two.</li>
<li><strong>What’s still in your system.</strong> The food and drink you’ve had, and how recently you’ve been to the toilet, all show up on the scales.</li>
<li><strong>Hormones.</strong> Many women weigh more in the days before a period.</li>
<li><strong>New exercise.</strong> After new or harder activity, muscles hold extra fluid while they recover.</li>
</ul>
<p>None of that is fat. And any of it can hide a week of progress.</p>

<h2>Look at the trend, not the day</h2>
<p>One weigh-in is a snapshot. A month of weigh-ins is a story. Weigh yourself at the same time and in the same way each time (first thing in the morning, after the toilet, before breakfast) and look at how the numbers move across four weeks. That’s the line that matters.</p>
<p>If daily weighing makes you anxious, weigh once a week instead. Either way, judge your progress by the month.</p>

<h2>Plateaus are part of the process</h2>
<p>Even when everything is going well, it’s normal for weight loss to slow down or pause for a while. As you lose weight, your body needs a little less energy than it did, so what worked at the start can have a smaller effect later on.</p>
<p>A pause of a week or two isn’t a sign that anything is wrong. If nothing has changed for a month or more, it’s worth a gentle review:</p>
<ul>
<li>Have portions crept up again, bit by bit?</li>
<li>Are there more snacks or drinks than you’d realised? Writing everything down for a few days can be revealing.</li>
<li>How are you sleeping? Short nights can leave you hungrier the next day.</li>
<li>Has your activity dropped off since the early weeks?</li>
</ul>
<p>Resist the urge to cut right back. Eating very little tends to backfire, leaving you hungry and tired, and more likely to give up altogether.</p>

<h2>Measure what the scales miss</h2>
<p>Measure your waist once a month, notice how your clothes fit, and keep track of how you feel: your energy, your sleep, how far you can walk. These often keep improving while the scales pause, and they give a truer picture of what’s changing.</p>

<h2>You don’t have to work it out alone</h2>
<p>If you’re a Together Clinic patient and your weight hasn’t changed for several weeks, or something doesn’t feel right, get in touch with our team. Our pharmacists are happy to talk it through with you.</p>
<p>A stubborn number on a Monday morning isn’t a verdict. It’s one reading on a long journey. Keep going, keep looking at the bigger picture, and give it time to show.</p>
HTML
        ),

        array(
            'slug'    => 'eating-well-on-a-smaller-appetite',
            'section' => 'your-treatment',
            'title'   => 'Eating well on a smaller appetite',
            'excerpt' => 'When you’re not as hungry as you used to be, what you eat matters more than ever. Here’s how to make smaller meals count.',
            'content' => <<<'HTML'
<p>When your appetite changes, one of the first things you might notice is how little you want to eat. Meals feel bigger than they used to. Snacks lose their pull. Some days, food doesn’t appeal much at all.</p>
<p>After years of battling hunger, that can feel like a relief. But it brings a new challenge: when you’re eating less, every mouthful needs to do more.</p>

<h2>Put protein first</h2>
<p>Protein helps you hold on to muscle while you lose weight, and it keeps you fuller for longer. When your appetite is small, it’s easy to fall short. Try to include some protein at every meal, and eat it first, so it doesn’t get left on the plate.</p>
<ul>
<li>Eggs</li>
<li>Greek yoghurt, skyr or cottage cheese</li>
<li>Chicken, turkey or fish</li>
<li>Tofu</li>
<li>Lentils, beans and chickpeas</li>
<li>Milk, in porridge, a smoothie or a latte</li>
</ul>

<h2>Smaller, more often</h2>
<p>Three large meals may feel like too much now. Four or five smaller ones spread through the day can be easier to manage. Use a smaller plate, eat slowly, and stop when you feel comfortably full rather than clearing the plate. It can take a little while for your body to register that it’s had enough.</p>

<h2>Don’t skip meals altogether</h2>
<p>When you’re not hungry, it’s tempting to skip meals. But going too long without eating makes it harder to get the protein, vitamins and minerals your body needs. On low-appetite days, aim for something small and nourishing at regular times: a yoghurt, a boiled egg, a glass of milk or a bowl of soup.</p>

<h2>Keep things moving</h2>
<p>Eating less can lead to constipation. To help:</p>
<ul>
<li>Include fibre: wholegrains, oats, fruit, vegetables, beans and lentils.</li>
<li>Drink plenty through the day. The NHS recommends 6 to 8 glasses of fluid a day, and water is best.</li>
<li>Stay active. Even a daily walk helps.</li>
</ul>

<h2>Go easy on rich and greasy food</h2>
<p>Large, fatty or greasy meals can sit heavily or make you feel sick, particularly in the early weeks. Lighter meals, and stopping before you’re overfull, tend to be more comfortable. It’s worth cutting back on alcohol too: it adds calories without nourishment, and it can make nausea worse.</p>

<h2>When to get in touch</h2>
<p>Contact our team if you’re struggling to eat or drink, if sickness or diarrhoea doesn’t settle, or if anything worries you.</p>
<p>If you can’t keep fluids down, have severe or ongoing stomach pain, or feel very unwell, get urgent medical advice from NHS 111. In an emergency, call 999.</p>

<h2>Make each meal count</h2>
<p>A smaller appetite is an opportunity as much as a challenge. Put protein first, look after the basics, and let us know how you’re getting on. We’re here for the whole of your treatment, not only the start.</p>
HTML
        ),

    );
}

add_action( 'init', 'ah_hh_create_launch_drafts', 30 );

function ah_hh_create_launch_drafts() {
    if ( get_option( 'ah_health_hub_launch_drafts_v1' ) ) {
        return;
    }

    $admins  = get_users( array( 'role' => 'administrator', 'number' => 1, 'orderby' => 'ID', 'fields' => 'ID' ) );
    $created = array();

    foreach ( ah_hh_launch_stories() as $story ) {
        // Already there in any state (draft, published, edited): leave it alone.
        if ( get_page_by_path( $story['slug'], OBJECT, 'post' ) ) {
            continue;
        }
        $term = get_term_by( 'slug', $story['section'], 'category' );
        $id   = wp_insert_post( array(
            'post_type'     => 'post',
            'post_status'   => 'draft',
            'post_title'    => $story['title'],
            'post_name'     => $story['slug'],
            'post_excerpt'  => $story['excerpt'],
            'post_content'  => $story['content'],
            'post_author'   => $admins ? (int) $admins[0] : 0,
            'post_category' => $term ? array( (int) $term->term_id ) : array(),
        ), true );
        if ( ! is_wp_error( $id ) ) {
            $created[] = $story['slug'];
        }
    }

    $summary = $created ? implode( ', ', $created ) : 'none created';
    update_option( 'ah_health_hub_launch_drafts_v1', gmdate( 'c' ) . ' | ' . $summary, false );
    error_log( '[ah-health-hub] launch drafts: ' . $summary );
}
