<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license MIT
 */

namespace Ebi\Tests\Faker;

use Faker\Address;
use Faker\Company;
use Faker\Faker;
use Faker\Name;

class Social extends Faker {
    protected static function sprintf($format, ...$args) {
        $parsedArgs = [];
        foreach ($args as $arg) {
            $parsedArgs[] = static::arg($arg);
        }

        $r = sprintf($format, ...$parsedArgs);
        $r = preg_replace('`\s+`', ' ', $r);
        return $r;
    }

    protected static function arg($arg) {
        if (!is_string($arg) && is_callable($arg)) {
            return call_user_func($arg);
        } elseif (is_array($arg)) {
            $first = reset($arg);
            if (is_string($first)) {
                if (preg_match('`^!concat(.*)$`', $first, $m)) {
                    array_shift($arg);
                    return static::concat(!empty($m[1]) ? $m[1] : ' ', ...$arg);
                } elseif ($first === '!if') {
                    array_shift($arg);
                    $condition = static::arg(array_shift($arg));

                    if ($condition) {
                        return static::arg($arg[0]);
                    } elseif (isset($arg[1])) {
                        return static::arg($arg[1]);
                    }
                }
            }
            $item = $arg[array_rand($arg)];
            return static::arg($item);
        }
        return $arg;
    }

    protected static function concat($glue, ...$args) {
        $pieces = array_map([static::class, 'arg'], $args);
        $r = implode($glue, $pieces);

        $r = preg_replace('`\s+`u', ' ', $r);
        $r = preg_replace('`\s+(\pP)`u', '$1', $r);

        return $r;
    }

    public static function question($ucwords = true) {
        $i = mt_rand(1, 5);

        $r = call_user_func([static::class, "question{$i}"]);
        return $ucwords ? self::ucwords($r) : $r;
    }

    private static function question1() {
        return self::concat(' ',
            [
                ['!concat', 'Where', self::maybe(['!concat', 'in', [Address::country(), Address::city()]])],
                'When', 'Why'
            ],
            ['should I', 'can I', 'do I'],
            self::maybe(self::adverb(), .2),
            self::verb(),
            self::maybe(self::noun(true)),
            '?'
        );
    }

    private static function question2() {
        $plural = self::coinFlip();

        return self::concat(' ',
            ['!if', $plural,
                ['What are', 'Can you explain what are'],
                ['What\'s your']
            ],
            self::maybe(self::adjective(), .8),
            self::noun($plural),
            self::maybe([
                'good for',
                'great at',
                'used with',
                'go with',
                'terrible at',
                'doing'
            ]),
            '?'
        );
    }

    private static function question3() {
        return self::concat(' ',
            [
                ['Why am I', 'Why are we'],
                ['!concat', 'Why are', self::noun(true)],
                ['!concat', 'Why is', [Name::name(), Address::city()]]
            ],
            self::maybe(self::adverb()),
            self::adjective(),
            '?'
        );
    }

    private static function ucwords($sentence) {
        $parts = explode(' ', $sentence);
        $first = true;
        $parts = array_map(function ($word) use (&$first) {
            if (!$first && in_array(strtolower($word), ['a', 'an', 'the', 'at', 'by', 'for', 'in', 'of', 'on', 'to', 'up', 'and', 'as', 'but', 'or', 'nor'])) {
                return strtolower($word);
            } else {
                $first = false;
                return ucfirst($word);
            }
        }, $parts);

        return implode(' ', $parts);
    }

    private static function question4() {
        return self::concat(' ',
            [
                ['How do I', 'How do you'],
                ['!concat', 'How do', self::noun(true)],
                ['!concat', 'How does', [Name::name(), Address::country()]]
            ],
            self::verb(),
            [self::aNoun(), self::noun(true)],
            self::maybe(self::adverb(), .3),
            '?'
        );
    }

    protected static function possesive($name) {
        if (substr($name, -1) === 's') {
            return $name."'";
        } else {
            return $name."'s";
        }
    }

    private static function question5() {
        return self::concat(' ',
            'Who',
            self::maybe('is', .8, 'was'),
            [
                ['!concat',
                    Name::name(),
                    self::maybe([
                        'seeing', 'dating', 'watching', 'dancing with', 'spying on', 'looking at', 'standing beside',
                        'following', 'promoting', 'friends with', 'with'
                    ], .3)
                ],
                ['!concat',
                    self::possesive(Name::name()),
                    [
                        'wife', 'husband', 'partner', 'daughter', 'son', 'spouse', 'Mom', 'Dad', 'cousin', 'pet',
                        'best friend', 'nemesis', 'rival', 'fiercest rival', 'mentor', 'protege', 'client', 'PM',
                        'manager'
                    ]
                ]
            ],
            '?'
        );
    }

    public static function clickBait($ucwords = true) {
        $i = mt_rand(1, 4);

        $r = call_user_func([static::class, "clickBait{$i}"]);
        return $ucwords ? self::ucwords($r) : $r;
    }

    private static function clickBait1() {
        $plural = static::coinFlip();

        $r = self::concat(' ',
            ['!if', $plural, ['These'], ['This', 'One', 'This one']],
            [
                static::maybe([static::class, 'adjective']),
                [static::maybe(static::adverb()), static::adjective()]
            ],
            static::noun($plural),
            ['!if', $plural, ['show us', 'show you', 'show'], ['shows us', 'shows you']],
            ['when', 'how'],
            static::maybe(static::adverb(), .3),
            static::adjective(),
            static::plural(),
            ['are', 'can be'],
            static::maybe(
                ['!concat', 'for', [static::class, 'plural']],
                .2
            ),
            static::maybe([
                ['!concat', 'in', [[Address::class, 'country'], [Address::class, 'city']]],
                ['!concat', 'at', [Company::class, 'name']]
            ], .2),
            '.'
        );

        return $r;
    }

    private static function coinFlip($sides = 2) {
        return mt_rand(0, $sides - 1);
    }

    /**
     * Maybe return a string.
     *
     * @param string $str The string to return.
     * @param float $percent A percent between 0 and 1.
     * @return string Returns {@link $str) or ''.
     */
    public static function maybe($str, $percent = .5, $else = '') {
        if (mt_rand(1, 10000) <= $percent * 10000) {
            return self::arg($str);
        } elseif ($else) {
            return self::arg($else);
        }
        return '';
    }

    public static function adverb() {
        return static::pickOne(['essentially', 'voluntarily', 'urgently', 'eventually', 'greatly', 'uselessly', 'dimly',
                'sweetly', 'mechanically', 'vainly', 'intensely', 'fully', 'somewhat', 'knowingly', 'boastfully', 'sedately',
                'inquisitively', 'tremendously', 'promptly', 'blissfully', 'literally', 'jealously', 'greedily',
                'separately', 'widely', 'rudely', 'joshingly', 'upwardly', 'previously', 'seriously', 'totally', 'annually',
                'thankfully', 'queerly', 'vastly', 'sternly', 'likely', 'highly', 'definitely',
                'quizzically', 'silently', 'yearningly', 'knavishly', 'gleefully', 'loudly', 'fervently', 'oddly',
                'searchingly', 'willfully', 'finally', 'smoothly', 'patiently', 'thoughtfully', 'loftily',
                'primarily', 'dreamily', 'energetically', 'anxiously', 'fatally',
                'joyfully', 'optimistically', 'technically', 'unimpressively', 'seldom', 'nervously', 'reluctantly',
                'recently', 'physically', 'heavily', 'zestily', 'correctly', 'mostly', 'relatively', 'wisely',
                'clearly', 'majestically', 'hungrily', 'suspiciously', 'always', 'successfully', 'immediately', 'diligently',
                'constantly', 'questioningly', 'wholly', 'carelessly', 'briefly', 'strongly', 'seemingly',
                'devotedly', 'hardly', 'naturally', 'carefully', 'noisily', 'only', 'beautifully', 'merely', 'frenetically',
                'wildly', 'crazily', 'generously', 'helpfully', 'tensely', 'officially', 'shrilly',
                'possibly', 'fortunately', 'elegantly', 'tightly', 'obnoxiously', 'mainly', 'nicely', 'abnormally',
                'usually', 'acidly', 'lightly', 'rigidly', 'madly', 'thoroughly',
                'enormously', 'doubtfully', 'viciously', 'cautiously', 'inwardly', 'wetly', 'colorfully', 'limply',
                'weekly', 'repeatedly', 'playfully', 'obediently', 'unaccountably', 'frequently', 'deceivingly',
                'valiantly', 'continually', 'unnecessarily', 'righteously', 'utterly',
                'gently', 'freely', 'ultimately', 'sadly', 'dramatically', 'slightly', 'unnaturally', 'mortally',
                'helplessly', 'frankly', 'partially', 'currently', 'overconfidently', 'intently', 'foolishly', 'crossly',
                'excitedly', 'interestingly', 'hastily', 'directly', 'usefully', 'rightfully', 'occasionally', 'closely',
                'unfortunately', 'truthfully', 'queasily', 'happily', 'gladly', 'youthfully', 'boldly', 'basically',
                'roughly', 'coolly', 'sharply', 'probably', 'surprisingly', 'longingly', 'dutifully', 'sheepishly',
                'triumphantly', 'joyously', 'especially', 'initially', 'softly', 'actually', 'daintily', 'nearly',
                'unabashedly', 'knottily', 'restfully', 'suddenly', 'kindheartedly', 'jubilantly', 'bleakly',
                'regularly', 'specifically', 'neatly', 'originally', 'yieldingly', 'stealthily', 'knowledgeably',
                'curiously', 'reproachfully', 'extremely', 'solemnly', 'generally', 'painfully', 'sleepily',
                'absentmindedly', 'famously', 'recklessly', 'shakily', 'briskly', 'frantically', 'weakly', 'wonderfully',
                'cruelly', 'vivaciously', 'victoriously', 'sympathetically', 'warmly', 'honestly', 'faithfully',
                'completely', 'gracefully', 'monthly', 'scarily', 'cleverly', 'strictly', 'commonly',
                'lazily', 'powerfully', 'steadily', 'jaggedly', 'equally', 'poorly', 'questionably', 'perfectly',
                'lovingly', 'instantly', 'hopelessly', 'bashfully', 'terrifically', 'furiously', 'cheerfully',
                'adventurously', 'simply', 'significantly', 'calmly', 'shyly', 'tediously', 'exactly', 'hourly', 'needily',
                'miserably', 'openly', 'terribly', 'deeply', 'reassuringly', 'arrogantly', 'solidly', 'merrily', 'tenderly',
                'speedily', 'quickly', 'healthily', 'delightfully', 'potentially', 'deftly', 'worriedly', 'already',
                'kiddingly', 'easily', 'badly', 'safely', 'really', 'fairly', 'slowly',
                'judgementally', 'justly', 'fiercely', 'broadly', 'kindly', 'eagerly', 'yawningly', 'keenly', 'loosely',
                'normally', 'owlishly', 'accidentally', 'ferociously', 'coaxingly', 'coyly', 'unbearably',
                'rapidly', 'deliberately', 'loyally', 'woefully', 'scarcely',
                'busily', 'violently', 'bitterly', 'evenly', 'daily', 'quirkily', 'awkwardly', 'similarly', 'brightly',
                'meaningfully', 'zealously', 'punctually', 'kookily', 'properly', 'enthusiastically', 'zestfully',
                'quarrelsomely', 'mysteriously', 'lively', 'courageously', 'swiftly', 'automatically', 'positively',
                'jovially', 'unexpectedly', 'rarely', 'defiantly', 'wrongly', 'blindly', 'early', 'dearly',
                'kissingly', 'effectively', 'virtually', 'verbally', 'politely', 'angrily', 'readily', 'certainly', 'yearly',
                'upliftingly', 'fondly', 'gratefully', 'quietly', 'mockingly', 'irritably', 'wearily',
                'frightfully', 'innocently', 'necessarily', 'bravely', 'truly', 'selfishly',
                'vacantly', 'personally', 'vaguely', 'hopefully', 'quaintly', 'offensively', 'unethically']
        );
    }

    public static function adjective() {
        return static::pickOne(['plastic', 'trashy', 'noisy', 'abstracted', 'deafening', 'madly', 'abiding', 'boring',
            'accurate', 'muddled', 'upset', 'windy', 'relieved', 'milky', 'sick', 'equable', 'separate', 'gusty',
            'deranged', 'sable', 'tricky', 'unnatural', 'unwritten', 'high', 'flagrant', 'crazy', 'crowded', 'grubby',
            'optimal', 'dysfunctional', 'unequal', 'responsible', 'victorious', 'jumpy', 'healthy', 'new', 'mere',
            'loose', 'fast', 'well-groomed', 'dry', 'thirsty', 'gainful', 'future', 'aromatic', 'spotty', 'chemical',
            'incredible', 'well-to-do', 'decisive', 'bloody', 'unruly', 'irritating', 'keen', 'jaded', 'exclusive',
            'elderly', 'happy', 'powerful', 'assorted', 'disgusting', 'highfalutin', 'sad', 'guarded', 'left', 'unused',
            'pleasant', 'yummy', 'doubtful', 'tangible', 'cloudy', 'abundant', 'aboard', 'horrible', 'fuzzy',
            'important', 'bite-sized', 'extra-large', 'careless', 'industrious', 'unknown', 'thoughtless', 'flawless',
            'boundless', 'incompetent', 'awful', 'cluttered', 'shivering', 'annoying', 'ratty', 'average', 'neat',
            'mundane', 'taboo', 'six', 'rebel', 'sophisticated', 'imported', 'lucky', 'impartial', 'four',
            'ruthless', 'motionless', 'real', 'demonic', 'conscious', 'dashing', 'scared', 'aback', 'melted', 'living',
            'melodic', 'detailed', 'grateful', 'hateful', 'wholesale', 'scarce', 'permissible', 'old', 'fabulous',
            'rigid', 'paltry', 'deeply', 'strong', 'unaccountable', 'spicy', 'ultra', 'hurried', 'long', 'far',
            'plucky', 'far-flung', 'luxuriant', 'insidious', 'handy', 'courageous', 'quiet', 'better', 'amuck',
            'obeisant', 'famous', 'panicky', 'purple', 'shut', 'gentle', 'handsome', 'majestic', 'vivacious', 'ripe',
            'broad', 'smart', 'subdued', 'profuse', 'fanatical', 'plain', 'wakeful', 'steep', 'womanly', 'venomous',
            'uncovered', 'worthless', 'frightening', 'moaning', 'mushy', 'disastrous', 'lyrical', 'wild', 'one',
            'imperfect', 'distinct', 'macabre', 'full', 'small', 'pumped', 'slim', 'fertile', 'fragile', 'mature',
            'strange', 'abortive', 'ubiquitous', 'dispensable', 'overt', 'wise', 'shy', 'bright', 'difficult',
            'oceanic', 'aloof', 'married', 'neighborly', 'dirty', 'gigantic', 'harmonious', 'simple', 'creepy',
            'lackadaisical', 'untidy', 'fearless', 'testy', 'vague', 'alleged', 'misty', 'bewildered', 'stingy',
            'dusty', 'smelly', 'silent', 'alike', 'hospitable', 'divergent', 'gray', 'wooden', 'nifty', 'roasted',
            'acidic', 'curved', 'adjoining', 'daffy', 'beneficial', 'flashy', 'addicted', 'childlike', 'giant', 'huge',
            'like', 'flippant', 'heady', 'irate', 'phobic', 'defiant', 'crooked', 'impolite', 'nostalgic', 'abrasive',
            'noxious', 'amazing', 'capable', 'tranquil', 'craven', 'same', 'lewd', 'exuberant', 'invincible', 'fluffy',
            'racial', 'late', 'dull', 'faint', 'romantic', 'temporary', 'parsimonious', 'bright', 'superb',
            'uninterested', 'humdrum', 'lethal', 'wiggly', 'observant', 'spotless', 'ajar', 'bent', 'envious', 'thick',
            'capricious', 'imminent', 'tenuous', 'dangerous', 'clumsy', 'ludicrous', 'old-fashioned', 'symptomatic',
            'juicy', 'fancy', 'hesitant', 'languid', 'petite', 'internal', 'guttural', 'resolute', 'bad', 'wealthy',
            'bashful', 'overjoyed', 'enormous', 'elegant', 'jazzy', 'fixed', 'next', 'tremendous', 'animated',
            'garrulous', 'two', 'wanting', 'hideous', 'halting', 'pink', 'adamant', 'innate', 'graceful', 'teeny',
            'actually', 'godly', 'wary', 'lively', 'present', 'righteous', 'confused', 'makeshift', 'giddy',
            'teeny-tiny', 'efficacious', 'splendid', 'cultured', 'juvenile', 'daily', 'open', 'lovely', 'calm',
            'useless', 'pale', 'aware', 'tense', 'cooing', 'subsequent', 'clever', 'resonant', 'damaged', 'rare',
            'cheerful', 'medical', 'diligent', 'repulsive', 'complete', 'nice', 'mighty', 'chubby', 'callous',
            'entertaining', 'miscreant', 'stupid', 'angry', 'agreeable', 'gaudy', 'skillful', 'toothsome', 'mindless',
            'true', 'damp', 'whole', 'legal', 'wonderful', 'cloistered', 'moldy', 'interesting', 'malicious',
            'glamorous', 'substantial', 'festive', 'poor', 'tame', 'tacit', 'little', 'obsequious', 'accidental',
            'white', 'receptive', 'hushed', 'earthy', 'unhealthy', 'female', 'natural', 'needless', 'unarmed', 'known',
            'slippery', 'eight', 'heavenly', 'measly', 'young', 'sloppy', 'unusual', 'needy', 'flat', 'forgetful',
            'merciful', 'shaky', 'alcoholic', 'wet', 'glossy', 'frantic', 'inexpensive', 'dynamic', 'cruel', 'heavy',
            'sudden', 'tender', 'zonked', 'dramatic', 'cynical', 'jobless', 'berserk', 'many', 'general', 'staking',
            'tiny', 'funny', 'classy', 'fine', 'glorious', 'rambunctious', 'satisfying', 'homeless', 'weak', 'jolly',
            'black-and-white', 'flowery', 'well-made', 'short', 'thirsty', 'gullible', 'somber', 'grieving', 'rampant',
            'filthy', 'delightful', 'alert', 'free', 'eatable', 'annoyed', 'furry', 'square', 'youthful', 'smooth',
            'draconian', 'beautiful', 'synonymous', 'tan', 'quickest', 'rude', 'cowardly', 'fierce', 'lonely',
            'nonstop', 'awesome', 'squealing', 'quack', 'placid', 'aggressive', 'tangy', 'electric', 'truculent',
            'vast', 'sparkling', 'absorbing', 'ordinary', 'obtainable', 'exotic', 'secretive', 'enchanted', 'erratic',
            'habitual', 'direful', 'puzzled', 'adventurous', 'mute', 'ten', 'nasty', 'ready', 'comfortable', 'everyday',
            'nutritious', 'mellow', 'therapeutic', 'grotesque', 'nosy', 'weary', 'pathetic', 'versed', 'trite', 'nutty',
            'depressed', 'sweet', 'ruddy', 'futuristic', 'unique', 'tearful', 'determined', 'blue', 'upbeat', 'absent',
            'nebulous', 'gamy', 'freezing', 'puzzling', 'calculating', 'discreet', 'damaging', 'bitter', 'dead', 'fantastic'
        ]);
    }

    public static function noun($plural = false) {
        if ($plural) {
            return static::plural();
        }

        return static::pickOne(['teen', 'cook', 'lake', 'shape', 'oatmeal', 'degree', 'action', 'cactus',
            'thing', 'number', 'kitten', 'discussion', 'stitch', 'zipper', 'pet', 'ball', 'writing', 'quill', 'soda',
            'suggestion', 'birth', 'alarm', 'title', 'desk', 'snake', 'corn', 'amusement', 'horse', 'pump', 'rub',
            'scale', 'arithmetic', 'powder', 'hair', 'cracker', 'geese', 'development', 'steam', 'weight', 'pear',
            'value', 'lunch', 'judge', 'rod', 'health', 'blood', 'crook', 'seat', 'girl', 'coil', 'society', 'flavor',
            'frog', 'monkey', 'quiet', 'rock', 'show', 'adjustment', 'basin', 'moon', 'trip', 'reading', 'icicle',
            'order', 'design', 'can', 'rabbit', 'road', 'vegetable', 'bush', 'temper', 'town', 'selection',
            'increase', 'cherry', 'son', 'mother', 'cap', 'gold', 'tooth', 'payment', 'spring', 'observation',
            'morning', 'smile', 'camp', 'bucket', 'boundary', 'friend', 'riddle', 'yarn', 'instrument', 'shade',
            'stew', 'flight', 'dirt', 'trouser', 'yard', 'ship', 'seashore', 'feeling', 'chin', 'unit',
            'coal', 'twig', 'quartz', 'pull', 'cow', 'mouse', 'tree', 'surprise', 'beginner', 'salt', 'bike',
            'representative', 'receipt', 'sense', 'advice', 'calculator', 'push', 'star', 'jelly', 'hand', 'turn',
            'mine', 'haircut', 'kettle', 'motion', 'current', 'rainstorm', 'zephyr', 'cemetery', 'effect', 'umbrella',
            'yoke', 'snail', 'crime', 'plot', 'jellyfish', 'dog', 'back', 'wave', 'box', 'porter', 'plough', 'offer',
            'expansion', 'sidewalk', 'boat', 'dust', 'sheet', 'meeting', 'parcel', 'bunker', 'sneeze',
            'mint', 'wine', 'cast', 'doll', 'veil', 'person', 'visitor', 'badge', 'girl', 'weather', 'need',
            'volcano', 'love', 'truck', 'spy', 'cover', 'flame', 'branch', 'wire', 'toy', 'brother', 'protest',
            'stick', 'grade', 'library', 'fang', 'hat', 'dinner', 'channel', 'donkey', 'marble', 'language', 'belief',
            'zebra', 'example', 'pleasure', 'pan', 'balance', 'lumber', 'match', 'bit', 'plane', 'wall', 'produce',
            'soap', 'shelf', 'tweet', 'building', 'crib', 'plastic', 'drop', 'basketball', 'record', 'territory',
            'ocean', 'bird', 'bat', 'sink', 'coat', 'mask', 'flower', 'room', 'quarter', 'machine', 'war', 'oil',
            'house', 'cannon', 'toothbrush', 'plant', 'wood', 'jam', 'screw', 'mountain', 'low', 'flesh', 'doll',
            'advertisement', 'request', 'detail', 'thing', 'children', 'slope', 'gate', 'government', 'interest',
            'wrist', 'book', 'rule', 'partner', 'plant', 'knowledge', 'dres', 'cub', 'talk', 'coast', 'plane',
            'memory', 'office', 'note', 'underwear', 'agreement', 'hate', 'hobbie', 'root', 'toothpaste', 'hill',
            'activity', 'fowl', 'sound', 'cream', 'needle', 'toy', 'giraffe', 'railway', 'form', 'place', 'account',
            'bomb', 'airplane', 'shirt', 'stocking', 'key', 'collar', 'income', 'plantation', 'smell', 'jean',
            'snail', 'bee', 'loss', 'toad', 'force', 'metal', 'wing', 'discovery', 'look', 'expert', 'business',
            'burst', 'front', 'hydrant', 'sock', 'side', 'desire', 'stream', 'duck', 'leg', 'governor', 'cake', 'sleet',
            'fire', 'trail', 'pin', 'behavior', 'tiger', 'treatment', 'tomato', 'angle', 'church', 'pollution',
            'bell', 'shake', 'measure', 'chalk', 'blow', 'join', 'circle', 'bear', 'wound', 'uncle', 'tire',
            'country', 'carriage', 'harbor', 'hot', 'ghost', 'fog', 'thrill', 'wind', 'page', 'rain', 'pipe', 'wealth',
            'hospital', 'bed', 'curtain', 'reaction', 'neck', 'cabbage', 'oven', 'lunchroom', 'arch', 'fairy',
            'baby', 'picture', 'pet', 'recipe', 'company', 'thought', 'sponge', 'theory',
            'condition', 'curve', 'maid', 'sweater', 'spade', 'division', 'tendency', 'wheel', 'frame', 'jewel',
            'grandfather', 'laugh', 'dime', 'baby', 'time', 'baseball', 'thread', 'sign', 'smash', 'industry', 'car',
            'crown', 'glass', 'change', 'appliance', 'house', 'book', 'furniture', 'shop', 'friend', 'bead',
            'thumb', 'pickle', 'tail', 'tank', 'crate', 'deer', 'silver', 'quicksand', 'aftermath', 'wax', 'door',
            'system', 'notebook', 'drain', 'mist', 'downtown', 'fireman', 'connection', 'rake', 'loaf',
            'nut', 'tramp', 'street', 'cheese', 'bath', 'daughter', 'view', 'throat', 'chicken', 'clam', 'end',
            'substance', 'pot', 'route', 'knife', 'train', 'day', 'truck', 'liquid', 'finger', 'bottle', 'knee',
            'minute', 'trick', 'magic', 'statement', 'orange', 'smoke', 'sofa', 'play', 'toe', 'line', 'writer',
            'fish', 'cent', 'bag', 'start', 'rifle', 'straw', 'kick', 'grip', 'voyage', 'dinosaur', 'cause', 'woman',
            'man', 'sand', 'square', 'humor', 'teaching', 'eye', 'grass', 'spoon', 'yak', 'rice', 'grain', 'creator',
            'arm', 'cable', 'vacation', 'horn', 'apparel', 'afterthought', 'night', 'part', 'texture', 'tongue',
            'string', 'army', 'elbow', 'hand', 'nerve', 'copper', 'crow', 'store', 'idea', 'anger', 'passenger',
            'song', 'amount', 'tub', 'caption', 'life'
        ]);
    }

    public static function plural($noun = '', $plural = true) {
        static $exceptions = ['sheep' => 'sheep', 'series' => 'series', 'species' => 'species', 'deer' => 'deer',
            'fish' => 'fish', 'child' => 'children', '', 'goose' => 'geese', '', 'man' => 'men', '', 'woman' => 'women',
            'tooth' => 'teeth', '', 'foot' => 'feet', '', 'mouse' => 'mice', '', 'person' => 'people', 'photo' => 'photos',
            'piano' => 'pianos', 'halo' => 'halos', 'popcorn' => 'popcorn', 'phenomenon' => 'phenomena', 'have' => 'has',
            'has' => 'have', 'knowledge' => 'knowledge'
        ];

        if (empty($noun)) {
            $noun = static::noun();
        } else {
            $noun = self::arg($noun);
        }

        if (!$plural) {
            return $noun;
        }

        if (isset($exceptions[$noun])) {
            return $exceptions[$noun];
        } elseif (preg_match('`(ss?|sh|ch|x|z)$`i', $noun)) {
            return $noun.'es';
        } elseif (preg_match('`[^aeiou]y$`i', $noun)) {
            return substr($noun, 0, -1).'ies';
        } elseif (preg_match('`o$`i', $noun)) {
            return $noun.'es';
        } elseif (preg_match('`us$`i', $noun)) {
            return substr($noun, 0, -2).'i';
        } elseif (preg_match('`is$`i', $noun)) {
            return substr($noun, 0, -2).'es';
//        } elseif (preg_match('`on$`i', $noun)) {
//            return substr($noun, 0, -2).'a';
        }

        $noun = preg_replace('`(f|fe)$`i', 've', $noun);

        return $noun.'s';
    }

    private static function clickBait2() {
        $r = self::concat(
            ' ',
            ['Watch', "We can't stop watching", "You won't believe what happens when", "That feeling when"],
            [Company::name(), Name::name(), Company::name()],
            self::maybe(self::adverb()),
            self::verb(),
            [
                static::noun(true),
                Address::country(),
                Company::name(),
            ],
            self::maybe(
                [
                    ['!concat', ['with', 'to'], static::noun(true)],
                ],
                .3
            ),
            '.'
        );

        return $r;
    }

    public static function verb($tense = '') {
        $verb = static::pickOne(['request', 'delight', 'rot', 'juggle', 'lick', 'switch', 'follow', 'skip', 'shelter',
            'walk', 'look', 'mark', 'treat', 'flash', 'kiss', 'fail', 'arrange', 'fetch', 'joke', 'fry', 'replace',
            'pop', 'wrestle', 'fade', 'behave', 'perform', 'turn', 'earn', 'cause', 'claim', 'groan', 'yawn', 'note',
            'supply', 'count', 'bow', 'deserve', 'need', 'preserve', 'film', 'lock', 'handle', 'sound',
            'own', 'slow', 'saw', 'confess', 'dislike', 'hum', 'save', 'pump', 'bump', 'moor', 'join', 'discover',
            'answer', 'bomb', 'dare', 'sail', 'damage', 'cure', 'murder', 'kick', 'interrupt', 'reflect',
            'start', 'separate', 'tumble', 'soak', 'zip', 'polish', 'terrify', 'tick', 'scream', 'choke',
            'peep', 'box', 'arrive', 'extend', 'point', 'sin', 'communicate', 'crash', 'hunt', 'coach',
            'appreciate', 'fire', 'paddle', 'guess', 'post', 'include', 'exercise', 'number', 'long', 'rely',
            'calculate', 'spray', 'guide', 'move', 'flood', 'compare', 'peel', 'pick', 'cheat', 'expect', 'comb',
            'laugh', 'rescue', 'reign', 'face', 'record', 'succeed', 'grip', 'tip', 'present', 'tempt',
            'plant', 'melt', 'concern', 'recognize', 'brake', 'snatch', 'detect', 'dry', 'charge', 'relax', 'reduce',
            'continue', 'puncture', 'crack', 'grate', 'sip', 'apologize', 'surround', 'welcome', 'thank',
            'suspend', 'stain', 'transport', 'wail', 'grin', 'haunt', 'park', 'delay', 'wander',
            'launch', 'watch', 'trip', 'confuse', 'mug', 'raise', 'smash', 'obey', 'order', 'radiate',
            'subtract', 'twist', 'beg', 'boast', 'roll', 'bore', 'please', 'shiver', 'fix', 'sniff', 'cross', 'ban',
            'shock', 'manage', 'trick', 'protect', 'reply', 'tickle', 'force', 'tug', 'annoy', 'bat',
            'whirl', 'scribble', 'wriggle', 'alert', 'invite', 'trouble', 'hurry', 'zoom', 'harm', 'depend',
            'wreck', 'bounce', 'owe', 'release', 'crawl', 'contain', 'describe', 'sack', 'spill', 'appear', 'mend',
            'suck', 'offend', 'press', 'soothe', 'imagine', 'jail', 'sign', 'close', 'argue', 'seal', 'time',
            'multiply', 'rush', 'pack', 'fence', 'doubt', 'guard', 'prepare', 'impress', 'suggest', 'bury',
            'promise', 'spark', 'cover', 'warn', 'dress', 'change', 'deceive', 'help', 'form', 'harass', 'meddle',
            'muddle', 'trade', 'buzz', 'tow', 'obtain', 'pine', 'paste', 'type', 'head', 'connect', 'load',
            'step', 'chew', 'talk', 'precede', 'race', 'march', 'pour', 'report', 'fax', 'pass', 'rejoice', 'touch',
            'scorch', 'hang', 'increase', 'shop', 'prick', 'match', 'mate', 'attend', 'balance', 'peck', 'attempt',
            'stretch', 'remember', 'plug', 'examine', 'scratch', 'push', 'reproduce', 'repair', 'complete', 'care',
            'itch', 'scrub', 'entertain', 'tour', 'wonder', 'tease', 'play', 'trap', 'reject', 'afford',
            'destroy', 'wave', 'clean', 'rub', 'tremble', 'invent', 'phone', 'mine', 'jump', 'squeak', 'wipe', 'flower',
            'weigh', 'surprise', 'test', 'spare', 'telephone', 'question', 'gaze', 'disappear', 'squash',
            'stitch', 'grab', 'tap', 'dam', 'plan', 'command', 'whip', 'risk', 'consist', 'cheer', 'trust',
            'wish', 'worry', 'untidy', 'wobble', 'clap', 'applaud', 'boil', 'brush', 'warm', 'cry', 'drown', 'vanish',
            'yell', 'sneeze', 'ruin', 'chop', 'scrape', 'add', 'observe', 'nail', 'drum', 'educate', 'nest', 'flow',
            'collect', 'whine', 'jog', 'notice', 'complain', 'glow', 'correct', 'allow', 'unite', 'scatter', 'improve',
            'attract', 'possess', 'borrow', 'bruise', 'train', 'beam', 'steer', 'unlock', 'moan', 'smell',
            'empty', 'carve', 'disapprove', 'employ', 'chase', 'remind', 'dream', 'pull', 'fear', 'introduce', 'admire',
            'blink', 'waste', 'enjoy', 'explain', 'bless', 'wash', 'heat', 'bolt', 'enter', 'suppose', 'suspect',
            'remain', 'stare', 'escape', 'excuse', 'hover', 'gather', 'call', 'love', 'smoke', 'challenge', 'tame',
            'retire', 'punish', 'sprout', 'stamp', 'ask', 'intend', 'carry', 'advise', 'dust', 'preach',
            'support', 'dance', 'eviscerate', 'injure', 'refuse', 'branch', 'arrest', 'sparkle', 'rain',
            'drag', 'heap', 'wink', 'object', 'scare', 'print', 'spoil', 'slap', 'work', 'want',
            'visit', 'wait', 'book', 'suffer', 'list', 'receive', 'compete', 'deliver', 'rhyme', 'clear',
            'found', 'interest', 'bare', 'ski', 'mourn', 'poke', 'serve', 'judge', 'camp', 'back', 'hop', 'mess up',
            'place', 'spell', 'live', 'return', 'fill', 'like', 'announce', 'decorate', 'suit', 'rinse',
            'approve', 'hug', 'breathe'
        ]);

        if (!empty($tense)) {
            $verb = static::verbTense($verb, $tense);
        }
        return $verb;
    }

    protected static function verbTense($verb, $tense = 'ing') {
        static $exceptions = [
            'ing' => [],
            'ed' => []
        ];

        if (isset($exceptions[$tense][$verb])) {
            return $exceptions[$tense][$verb];
        } elseif (preg_match('`[^ia]e$`i', $verb)) {
            // Verbs ending with a silent e
            return substr($verb, 0, -1).$tense;
        } elseif ($tense === 'ed' && preg_match('`[^aeiou]y$`i', $verb)) {
            return substr($verb, 0, -1).'i'.$tense;
//        } elseif (preg_match('`[aeiou]l$`i', $verb)) {
            // Verbs ending with a vowel plus -l
//            return $verb."l$sx";
        } elseif (preg_match('`[^aeiou][aeiou][mpgbn]$`i', $verb)) {
            return $verb.substr($verb, -1).$tense;
        } elseif (preg_match('`c$`i', $verb)) {
            // Verbs ending in -c
            return $verb."k$tense";
        }

        return $verb.$tense;
    }

    private static function clickBait3() {
        $r = static::concat(
            ' ',
            [
                ['!concat', ['This', 'One'], self::noun()],
                [ucfirst(self::aNoun())]
            ],
            [
                self::verb('ed'),
                ['!concat', 'is', self::verb('ing')]
            ],
            [
                [static::class, 'aNoun'],
                [Address::class, 'country']
            ],
            [
                ['!concat', 'on', static::startup()],
                ['!concat', 'in', Address::country()]
            ],
            'and',
            ['people', 'women', 'men', 'teens', 'all of us'],
            'are',
            [
                'dying', 'dying laughing', 'flipping out', 'scared', 'watching', 'noticing', 'suffering', 'cursing', 'smiling',
                'running', 'talking', 'emotional', 'going nuts', 'going crazy', 'like "meh"', 'like wow', 'balling'
            ],
            '.'
        );

        return $r;
    }

    private static function aNoun() {
        $noun = static::noun();

        if (preg_match('`^[aeiou]`', $noun)) {
            return "an $noun";
        } else {
            return "a $noun";
        }
    }

    public static function startup() {
        $sx = ['ify', 'r', 'eme', 'ly'];

        switch (static::coinFlip()) {
            case 0:
                return ucfirst(static::adjective()).ucfirst(static::noun());
            case 1:
                return ucfirst(static::verb()).$sx[array_rand($sx)];
        }
    }

//    public static function pickOne(array $options) {
//        static $i = 0;
//        if ($i >= count($options)) {
//            return '';
//        }
//
//        return $options[$i++ % count($options)];
//    }

    private static function clickBait4() {
        $plural = static::coinFlip();

        $r = self::concat(
            ' ',
            [mt_rand(5, 15), [20, 24, 25, 30, 33, 42, 50, 66, 75, 99, 100]],
            [
                [static::class, 'adjective'],
                [
                    '!concat',
                    'of the',
                    ['best', 'worst', 'most interesting', 'craziest']
                ]
            ],
            [static::class, 'plural'],
            [
                '!if', $plural,
                ['most', 'all', 'literally all', 'almost all', ''],
                ['every', 'literally every', 'almost every', 'no'],
            ],
            self::plural(['person', 'vegetarian', 'coworker', 'dinosaur', 'ninja'], $plural),
            self::maybe(['probably', 'definitely', 'apparently', 'no doubt', 'possibly', 'seemingly', 'believably']),
            [
                [self::plural(['have', 'hate', 'love'], !$plural)],
                ['!concat', self::plural('has', $plural), ['suffered through', 'lost', 'been annoyed by', 'done', 'laughed at', 'cried about']],
                ['!concat', ['should', 'will'], ['recognize', 'love', 'eat', 'destroy', 'relate to', 'share', 'forward'], self::maybe(['immediately', 'for sure'], .3)]
            ],
            '.'
        );
        return $r;
    }
}
