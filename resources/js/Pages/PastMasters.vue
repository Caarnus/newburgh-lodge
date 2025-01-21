<script setup>

import AppLayout from "@/Layouts/AppLayout.vue";
import {ref} from "vue";

const pastMasters = ref([
    { year: "2025", name: "David Brickey II" },
    { year: "2024", name: "Chad Hostetter" },
    { year: "2023", name: "Brandon Goodall" },
    { year: "2022", name: "Ken Mitz" },
    { year: "2021", name: "Jason Warren" },
    { year: "2020", name: "Ken Mitz" },
    { year: "2019", name: "Ron Markham" },
    { year: "2018", name: "Paul Rainey" },
    { year: "2017", name: "Chad Steckler" },
    { year: "2016", name: "Mark Lewellyn" },
    { year: "2015", name: "David Hart" },
    { year: "2014", name: "Benjamin Larramore" },
    { year: "2013", name: "Kevin L. Cobb" },
    { year: "2012", name: "Ronald E. Millikan" },
    { year: "2011", name: "Martin R. Helm" },
    { year: "2010", name: "Jacob Hebner" },
    { year: "2009", name: "Charles Milligan" },
    { year: "2008", name: "Frank G. Bolin" },
    { year: "2007", name: "Randall E. Beem" },
    { year: "2006", name: "Garry Bradley" },
    { year: "2005", name: "Paul E. Rainey" },
    { year: "2004", name: "Robert E. Addington", deceased: true },
    { year: "2003", name: "Dennis T. Bolin" },
    { year: "2002", name: "Terry G. Brown" },
    { year: "2001", name: "Danial T. Brown" },
    { year: "2000", name: "Harold A. Bloss" },
    { year: "1999", name: "Loren T. Dixon" },
    { year: "1998", name: "Michael E. Cannon", deceased: true },
    { year: "1997", name: "William C. Peppiatt", deceased: true },
    { year: "1996", name: "Brian R. Burdette" },
    { year: "1995", name: "E. Ray Bradley III" },
    { year: "1994", name: "C. Michael Hurford" },
    { year: "1993", name: "Jesse D. Hurford" },
    { year: "1992", name: "Ned P. Albright" },
    { year: "1991", name: "Forrest A. Youngs" },
    { year: "1990", name: "Hugh E. Metz" },
    { year: "1989", name: "Harold W. Harkins", deceased: true },
    { year: "1988", name: "J.L. Jimmy Shackelford IV" },
    { year: "1987", name: "Ralph A. Pigman" },
    { year: "1986", name: "Henry C. Youngs", deceased: true },
    { year: "1985", name: "Garey W. Patmore", deceased: true },
    { year: "1984", name: "Bobby J. Wakeland", deceased: true },
    { year: "1983", name: "Paul E. Fridy", deceased: true },
    { year: "1982", name: "Richard Allen Wise", deceased: true },
    { year: "1981", name: "J.L. Bud Shackelford", deceased: true },
    { year: "1980", name: "W. Wayne Walker" },
    { year: "1979", name: "Ronald O. Collins" },
    { year: "1978", name: "Hugh E. Ditterline" },
    { year: "1977", name: "Jerry R. Duncan", deceased: true },
    { year: "1976", name: "Burnie Riley", deceased: true },
    { year: "1975", name: "Elmer Hinman", deceased: true },
    { year: "1974", name: "Harry W. Brown" },
    { year: "1973", name: "Clifton G. Duckworth", deceased: true },
    { year: "1972", name: "Bennie Johnson" },
    { year: "1971", name: "Warren Miller", deceased: true },
    { year: "1970", name: "Clifton Ashby" },
    { year: "1969", name: "Joseph L. Furno", deceased: true },
    { year: "1968", name: "Alpha C. Owens", deceased: true },
    { year: "1967", name: "Donald R. Ingler", deceased: true },
    { year: "1966", name: "Lester James Cox", deceased: true },
    { year: "1965", name: "Gaylon L. Clark Jr." },
    { year: "1964", name: "George A, Schneider", deceased: true },
    { year: "1963", name: "Carl J, Speis", deceased: true },
    { year: "1962", name: "Elliott C. Porter" },
    { year: "1960-61", name: "Earl F. Bickmier", deceased: true },
    { year: "1959", name: "Herman J. Staser", deceased: true },
    { year: "1958", name: "Simon H. Purdue", deceased: true },
    { year: "1957", name: "James V. Gold", deceased: true },
    { year: "1956", name: "Dowell E. Thompson", deceased: true },
    { year: "1955", name: "Chester S. Vanada", deceased: true },
    { year: "1954", name: "Harry W. Groot" },
    { year: "1953", name: "Allan Techenor" },
    { year: "1952", name: "John McDaniel", deceased: true },
    { year: "1951", name: "Almond H. Akin" },
    { year: "1950", name: "Charles L. Didelotte", deceased: true },
    { year: "1949", name: "William E. Wells", deceased: true },
    { year: "1948", name: "Robert N. Hargrave", deceased: true },
    { year: "1947", name: "S. Forrest Stitt", deceased: true },
    { year: "1946", name: "Weston E. Booth", deceased: true },
    { year: "1945", name: "Carl A. Smith", deceased: true },
    { year: "1944", name: "Ronald W. Hoge", deceased: true },
    { year: "1943", name: "Harry W. Hoge", deceased: true },
    { year: "1942", name: "George F. Morrison", deceased: true },
    { year: "1941", name: "E. Elwood Autra", deceased: true },
    { year: "1940", name: "E. Marion Autra", deceased: true },
    { year: "1939", name: "Newman Culver", deceased: true },
    { year: "1938", name: "James M. Gardner", deceased: true },
    { year: "1937", name: "Bernard N. Barclay", deceased: true },
    { year: "1936", name: "James G. Crenshaw", deceased: true },
    { year: "1935", name: "Louis S. Chivan", deceased: true },
    { year: "1934", name: "Harry A. Law", deceased: true },
    { year: "1933", name: "Robert T. Colvin", deceased: true },
    { year: "1932", name: "M. Ladd Greenwell", deceased: true },
    { year: "1931", name: "Harry A. Law", deceased: true },
    { year: "1930", name: "James E. Cox", deceased: true },
    { year: "1928-29", name: "Landry C. Conway", deceased: true },
    { year: "1927", name: "William J. Hooker", deceased: true },
    { year: "1926", name: "James E. Cox", deceased: true },
    { year: "1925", name: "Samuel Petty", deceased: true },
    { year: "1924", name: "Landry C. Conway", deceased: true },
    { year: "1923", name: "Henry A. Bippus", deceased: true },
    { year: "1922", name: "Oscar C. Brizius", deceased: true },
    { year: "1921", name: "John F. Grimm", deceased: true },
    { year: "1920", name: "Walker W. Hargrave", deceased: true },
    { year: "1919", name: "Raleigh O. Humphrey", deceased: true },
    { year: "1917-18", name: "George W. Burke", deceased: true },
    { year: "1916", name: "John W. Murnaham", deceased: true },
    { year: "1915", name: "Neal C. Medcalf", deceased: true },
    { year: "1914", name: "Walker W. Hargrave", deceased: true },
    { year: "1913", name: "Adolph H. Martin", deceased: true },
    { year: "1912", name: "Walker W. Hargrave", deceased: true },
    { year: "1911", name: "William Smith", deceased: true },
    { year: "1910", name: "John Curt de Forrest", deceased: true },
    { year: "1909", name: "Adolph H. Martin", deceased: true },
    { year: "1908", name: "Louis E. Hargrave", deceased: true },
    { year: "1907", name: "Edward C. Kay", deceased: true },
    { year: "1904-06", name: "Adolph H. Martin", deceased: true },
    { year: "1903", name: "James W. Holder", deceased: true },
    { year: "1902", name: "Charles W. Brizius", deceased: true },
    { year: "1901", name: "James W. Holder", deceased: true },
    { year: "1900", name: "Charles W. Brizius", deceased: true },
    { year: "1899", name: "Dudley Blankenship", deceased: true },
    { year: "1897-98", name: "Charles W. Brizius", deceased: true },
    { year: "1896", name: "Walker W. Hargrave", deceased: true },
    { year: "1895", name: "James W. Holder", deceased: true },
    { year: "1894", name: "Hollis W. de Forest", deceased: true },
    { year: "1893", name: "D. M. Frame", deceased: true },
    { year: "1890-92", name: "Walker W. Hargrave", deceased: true },
    { year: "1887-89", name: "Louis Hulvershorn", deceased: true },
    { year: "1885-86", name: "John S. Robertson", deceased: true },
    { year: "1884", name: "S. W. McNaughten", deceased: true },
    { year: "1882-83", name: "D. M. Frame", deceased: true },
    { year: "1881", name: "William Butterworth", deceased: true },
    { year: "1879-80", name: "John J. Knapp", deceased: true },
    { year: "1878", name: "William S. Garwood", deceased: true },
    { year: "1877", name: "John J. Knapp", deceased: true },
    { year: "1873-76", name: "John S. Robertson", deceased: true },
    { year: "1870-72", name: "William W. Hunt", deceased: true },
    { year: "1869", name: "William S. Garwood", deceased: true },
    { year: "1868", name: "A. Judson Fish", deceased: true },
    { year: "1865-67", name: "William S. Garwood", deceased: true },
    { year: "1864", name: "Albert Hazen", deceased: true },
    { year: "1863", name: "Daniel F. Bates", deceased: true },
    { year: "1862", name: "John W. Pearce", deceased: true },
    { year: "1861", name: "Benjamin Hall", deceased: true },
    { year: "1860", name: "John W. Pearce", deceased: true },
    { year: "1858-59", name: "F. D. Bullett", deceased: true },
    { year: "1857", name: "Hiram M. Hargrave", deceased: true },
])
</script>

<template>
    <AppLayout title="Past Masters">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Past Masters
            </h2>
        </template>
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                    <div class="dark:bg-surface-900 dark:text-surface-200 font-sans">
                        <div class="max-w-3xl mx-auto p-6">
                            <div class="overflow-x-auto">
                                <table class="w-full table-auto border-collapse">
                                    <thead>
                                    <tr>
                                        <th class="text-primary-700 text-right px-4 py-2">Year</th>
                                        <th class="text-primary-700 text-left px-4 py-2">Name</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <tr v-for="(master, index) in pastMasters" :key="index" class="even:bg-surface-200">
                                        <td class="text-right px-4 py-2">{{ master.year }}</td>
                                        <td class="text-left px-4 py-2">
                                            {{ master.name }} <span v-if="master.deceased" class="text-red-500">*</span>
                                        </td>
                                    </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>

</style>
